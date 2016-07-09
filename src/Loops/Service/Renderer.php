<?php
/**
 * This file is part of the Loops framework.
 *
 * @author Lukas <lukas@loopsframework.com>
 * @license https://raw.githubusercontent.com/loopsframework/base/master/LICENSE
 * @link https://github.com/loopsframework/base
 * @link https://loopsframework.com/
 * @version 0.1
 */

namespace Loops\Service;

use ArrayAccess;
use Doctrine\Common\Cache\ChainCache;
use Doctrine\Common\Cache\ArrayCache;
use Loops;
use Loops\Annotations\Access\ReadOnly;
use Loops\ArrayObject;
use Loops\ElementInterface;
use Loops\Exception;
use Loops\Misc;
use Loops\Renderer\RenderPluginInterface;
use Loops\Renderer\CacheInterface;
use Loops\Renderer\RenderException;
use Loops\Renderer\CustomizedRenderInterface;
use Loops\Service;
use ReflectionMethod;

/**
 *　The Loops renderer
 *
 * The Loops renderer will display any object based on the Loops renderer logic.
 * It will select a template based on the objects classname and requested appearance tags.
 * Appearance tags are keywords that can be accociated with templates.
 * During the template selection process the presence of appearance tags can be forced (the tag must be associated) or optional (if the tag is accosiated the template will be preferred).
 *
 * Template engines can be implemented via plugins.
 * The selected plugin for a template file will be selected based on the file extension.
 * It is possible but not recommended to use different template engines for different parts (objects) of a rendered page.
 * 
 */
class Renderer extends Service {
    private $views = [];
    private $stack = [];
    private $appearance_stack = [];
    private $registered_plugins = [];
    private $extra_appearances = [];
    
    protected static $default_config = [ "view_dir" => [ "views" ] ];
    
    /**
     * @ReadOnly
     */
    protected $view_dir;
    
    public function __construct($view_dir = [], Loops $loops = NULL) {
        parent::__construct($loops);
        
        $this->view_dir = array_map(function($dir) {
            return Misc::fullPath($dir, $this->getLoops()->getService("application")->app_dir);
        }, (array)$view_dir);
        
        $this->view_dir = array_filter($this->view_dir, "is_dir");
        
        $loops_views = realpath(__DIR__."/../../../views");
        
        if(!in_array($loops_views, $this->view_dir)) {
            $this->view_dir[] = $loops_views;
        }
    }
    
    /**
     * Manually register a renderer plugin (by classname)
     *
     * This method can override previously declared plugins
     *
     * @param string $name The file extension for which the plugin should be registered.
     * @param string $classname The name of a class that implements Loops\Renderer\RenderPluginInterface.
     */
    public function registerRenderPlugin($name, $classname) {
        $this->registered_plugins[$name] = $classname;
    }
    
    /**
     * Adds an optional appearance tag that will be used for all render calls.
     *
     * Additional optional appearance tags can be used to pass variables that define the environment.
     * For example 'cli' if working in a client interface environment.
     * Having these appearance tags, it is possible to define template files that will be preferably used in these environments.
     *
     * @param string $appearance The name of the appearance tag
     * @param bool $before Specifies if the passed appearance tag should be appended or prepended to the array of extra apperance tags.
     */
    public function addExtraAppearance($appearance, $before = FALSE) {
        if($before) {
            array_unshift($this->extra_appearances, $appearance);
        }
        else {
            array_push($this->extra_appearances, $appearance);
        }
    }

    /**
     * Helper that scans the view dirs and stores the inforation in the cache
     */
    private function loadViews() {
        if($this->views) {
            return;
        }

        //scan views - and store result in cache
        $cache = $this->getLoops()->getService("cache");
        $key = "Loops-Renderer-".implode("-", $this->view_dir);
        if($cache->contains($key)) {
            $this->views = $cache->fetch($key);
        }
        else {
            foreach($this->view_dir as $view_dir) {
                $this->scanViewDir($view_dir);
            }
            $cache->save($key, $this->views);
        }
    }
    
    /**
     * Returns the render output for an object or variable
     *
     * The formal rules of the rendering process are explained here but for a more practical explanation, please consult the Loops user manual.
     * 
     * Step 1 - Appearance tag compilation
     *
     * Rules:
     *  - The renderer class manages a list of "forced appearance tags" and "optional appearance tags" which are persistant on nested calls.
     *  - Forced appearance tags are required and the template must have them associated in order to be selected.
     *  - Optional appearance tags are NOT required but templates that have them associated are preferably selected.
     *  - Appearances should be passed as an array in the parameters, if it is a string it will be splitted into one by using '.' (the dot character).
     *  - Duplicate appearance tags will be ignored.
     *  - In nested calls, the passed appearances tags will be APPENDED to the list(s) of current appearance tags.
     *  - Removing appearance tags triggered by $clear and $fallback will occur before the new ones from $appearance and $optional are appended to the list.
     *  - If $appearance, $clear or $optional is set to boolean TRUE, it will be treated as if the $fallback parameter would have been set to TRUE instead. (Syntethic sugar)
     *  - Extra appearance tags that can be registered by the "addExtraAppearance" method will be appended to the optional appearance tag list.
     *
     * Step 2 - Template selection
     *
     * Rules:
     *  - The file extension of a template defines the renderer plugin name.
     *  - Split the remaining filename of a template by the '.' (dot character). The fist element defines the "template name" and the other elements, if any, define the list of associated appearances for the template.
     *    -> There can exist multiple templates with the same "template name" but with differently accociated appearance tags.
     *  - Find the "template name" that should be used by getting the classname of the passed $object
     *  - If it is not an object, use its variable type as the "template name" (by consulting PHPs gettype function).
     *  - Choose the template that matches the "template name" and have all associated apperance parameters listed either in the forced appearance tag list or the optional appearance tag list.
     *  - The order of the accociated appearance tags matter and must match the request. Forced appearance tags must be defined before optional appearance tags.
     *
     * Special rules when the "template name" contains one or more '-' (dash characters):
     *  - If the following conditions match for a template, prefer it over a match from normal rules.
     *  - Split the "template name" into a "parent objects template name" and one or more "offsets"
     *  - $object must be an object and implement Loops\ElementInterface
     *  - The "getName" method must match the last offset. Remove the "offset" and get the parent element by the "getParent" method.
     *  - If "offsets" remain, repeat the above checks (Loops\ElementInterface check, name check) until there are no more offsets left. All conditions must match.
     *  - The current parent elements class name must match "parent objects template name"
     *  - Appearance tags must also match as in the normal rules.
     *
     * If no template matches the above rules/conditions, a Loops\Renderer\RendererException is thrown.
     *
     * Step 3 - Prepare the template engine
     *
     *  Based on the file extension of the selected template file, a renderer plugin is instantiated.
     *  If a class has been registered for an extension by the "registerRenderPlugin" method, it will be used.
     *  Otherwise the renderer will look for a Loops service called %_renderer where % denotes the file extension with the first letter capitalized.
     *  The service has to implement the Loops\Renderer\RenderPluginInterface.
     *  If $object is traversable, the renderer will register all key => value pairs with the template engine.
     *  Afterwards, the following additionals variable will be registered:
     *  
     *  Basic helper:
     *   "this": Set to the $object itselft
     *   "stack": An array of all currently rendered $objects. $stack[0] is the same as $object
     *   "loops": The Loops context of the element if it is a Loops\Object or the one from this renderer otherwise
     *
     *  Values for convenience in a web application:
     *   "domain": The base_url value in the application section of the loops config service, if it exists
     *   "query": The value that is returned from the "getQuery" method of the request service, if it exists
     *
     * These variables my overwrite previously registered values.
     *
     * Step 4 - Rendering
     *   Finally, the template is rendered with the template engine.
     *   During the rendering process, the render method may be called again and with different parameters.
     *   There is no recursion protection.
     *   The output of the template engine will be returned
     *   
     *
     * @todo Move convenience variables into the Application class + add a method here to set them 
     *  
     * @param mixed $object The object or variable that should be rendered
     * @param string|array<string>|TRUE An array of forced appearance tags (or a single appearance tag). The list will accumulate on nested requests.
     * @param string|array<string>|TRUE An array of appearance tags (or a single appearance tag) that will be REMOVED from the currently accumulated forced and/or optional appearance list.
     * @param string|array<string>|TRUE An array of optional appearance tags (or a single appearance tag). The list will accumulate on nested requests.
     * @param bool $fallback If set to true, reset the current list of forced and optional appearance tags.
     * @return string The output
     */
    public function render($object, $appearance = [], $clear = [], $optional = [], $fallback = FALSE) {
        $this->loadViews();

        $args = func_get_args();
        if(array_pop($args) === TRUE) {
            $fallback      = TRUE;
            $pos = func_num_args();
            if($pos == 1) $fallback = FALSE;
            if($pos == 2) $appearance = [];
            if($pos == 3) $clear = [];
            if($pos == 4) $optional = [];
        }
        
        //prepare
        $appearance = array_filter(is_array($appearance) ? $appearance : explode(".", (string)$appearance));
        
        list($current_appearance, $current_optional) = end($this->appearance_stack) ?: [[],[]];
        
        if($fallback === TRUE) {
            $optional = $current_appearance;
        }
        
        $current_appearance = array_diff($current_appearance, $appearance);
        $current_appearance = array_merge($current_appearance, $appearance);

        if($optional) {
            $optional = array_filter(is_array($optional) ? $optional : explode(".", (string)$optional));
            
            $current_appearance = array_unique(array_merge($current_appearance, $optional));
            $current_optional   = array_unique(array_merge($current_optional, $optional));
        }
        
        if($clear) {
            if($clear === TRUE) {
                $current_appearance = $appearance;
                $current_optional   = $optional;
            }
            else {
                $clear = array_filter(is_array($clear) ? $clear : explode(".", (string)$clear));
                
                $current_appearance = array_diff($current_appearance, $clear);
                $current_optional   = array_diff($current_optional, $clear);
            }
        }

        if($object instanceof CustomizedRenderInterface) {
            $object->modifyAppearances($current_appearance, $current_optional);
            $current_optional = array_unique($current_optional);
            $current_appearance = array_unique(array_merge($current_appearance, $current_optional));
        }
        
        $select_appearance = array_unique(array_merge($current_appearance, $this->extra_appearances));
        $select_optional   = array_unique(array_merge($current_optional, $this->extra_appearances));
        
        $cache_key = FALSE;
        
        //check cache
        if($object instanceof CacheInterface) {
            if($object->isCacheable()) {
                $lifetime = $object->getCacheLifetime();
                $cache_key = "Renderer-render-".get_class($object)."-".implode("*", $select_appearance)."-".implode($select_optional)."-".$object->getCacheId();
                $loops = $this->getLoops();
                $cache = $loops->getService("cache");
                if($cache->contains($cache_key)) {
                    return $cache->fetch($cache_key);
                }
            }
        }
        
        //resolve file
        if(is_object($object)) {
            $template = $this->resolveObject($object, $select_appearance, $select_optional);

            if($object instanceof CustomizedRenderInterface) {
                if($delegation = $object->delegateRender()) {
                    $object = $delegation;
                }
            }
        }
        else {
            $template = $this->resolveOther($object, $select_appearance, $select_optional);
        }

        if(!$template) {
            $type = str_replace(" ", "", is_object($object) ? get_class($object) : gettype($object));
            
            $error = "Failed to find a template for object of type '$type'";
            
            if($object instanceof CustomizedRenderInterface && $templatename = $object->getTemplateName()) {
                $error .= " with custom template '$templatename'";
            }

            if($object instanceof ElementInterface) {
                $error .= " and with loopsid '".$object->getLoopsId()."'";
            }
            
            $error .= ".";

            throw new RenderException($error, $object, $select_appearance, array_diff($select_appearance, $select_optional));
        }
        
        //fire event if available
        if(is_object($object)) {
            static $event_fired = [];
            
            $hash = spl_object_hash($object);
            
            if(empty($event_fired[$hash]) && method_exists($object, "fireEvent")) {
                $object->fireEvent("Renderer\onRender");
                $event_fired[$hash] = TRUE;
            }
        }
        
        //start preparing the rendering process
        list($plugin_name, $folder, $file, $appearance) = $template;
        
        $current_optional = array_diff($current_optional, $appearance);
        
        array_push($this->appearance_stack, [ $current_appearance, $current_optional ]);
        
        $added = FALSE;
        if(!$this->stack || ($this->stack[0] !== $object)) {
            $added = TRUE;
            array_unshift($this->stack, $object);
        }

        try {
            //prepare template variables
            $vars = [];
            
            if(is_object($object) || is_array($object)) {
                foreach($object as $key => $value) {
                    $vars[$key] = $value;
                }
            }
            
            $vars["this"]   = $object;
            $vars["loops"]  = $loops = $this->getLoops();
            $vars["stack"]  = $this->stack;
            
            if($loops->hasService("web_core")) {
                $vars["domain"] = $loops->getService("web_core")->base_url;
            }
            
            if($loops->hasService("request")) {
                $vars["query"]  = $loops->getService("request")->getQuery();
            }
        
            //get plugin from Loops Context
            $servicename = $plugin_name."_renderer";
            
            $plugin = $this->getLoops()->getService($servicename);
            
            if(!($plugin instanceof RenderPluginInterface)) {
                throw new Exception("Renderplugin '$plugin_name' (Service: $servicename) must implement 'Loops\Renderer\RenderPluginInterface'.");
            }
            
            foreach($vars as $key => $value) {
                $plugin->addVar($key, $value);
            }
        
            $result = $plugin->render($folder, $file);
        }
        finally {
            if($added) {
                array_shift($this->stack);
            }
            
            array_pop($this->appearance_stack);
        }

        if($cache_key) {
            $cache->save($cache_key, $result);
        }
        
        return $result;
    }
    
    private function _resolve(&$views, $loopsidparts, $appearance, $force) {
        if($loopsidparts) {
            $key = "-".array_shift($loopsidparts);
            
            if(!array_key_exists($key, $views)) {
                return FALSE;
            }
            
            return $this->_resolve($views[$key], $loopsidparts, $appearance, $force);
        }
        
        while($appearance) {
            $key = ".".array_shift($appearance);
            
            if(!array_key_exists($key, $views)) {
                continue;
            }
            
            if($result = $this->_resolve($views[$key], $loopsidparts, $appearance, $force)) {
                return $result;
            }
        }
        
        if(array_key_exists(".", $views) && !array_diff($force, $views["."][3])) {
            return $views["."];
        }
        
        return FALSE;
    }
    
    private function resolveClass($type, $appearance, $optional) {
        if(!array_key_exists($type, $this->views)) {
            return FALSE;
        }
        
        return $this->_resolve($this->views[$type], [], $appearance, array_diff($appearance, $optional));
    }
    
    private function resolveOther($var, $appearance, $optional) {
        $type = str_replace(" ", "", strtolower(gettype($var)));
        
        if($result = $this->resolveClass($type, $appearance, $optional)) {
            return $result;
        }
        
        if($result = $this->resolveClass("other", $appearance, $optional)) {
            return $result;
        }
    
        return FALSE;
    }
    
    private function resolveByPagePath(ElementInterface $object, $delegation, $loopsidparts, $appearance, $optional) {
        if($parent = $object->getParent()) {
            array_unshift($loopsidparts, $object->getName());
            
            if(!$object::isPage()) {
                if($result = $this->resolveByPagePath($parent, $delegation, $loopsidparts, $appearance, $optional)) {
                    return $result;
                }
            }
            
            array_shift($loopsidparts);
        }
        
        if($loopsidparts) {
            $classname = get_class($object);
            
            if($object instanceof CustomizedRenderInterface && $templatename = $object->getTemplateName()) {
                $templatename = str_replace("/", "\\", $templatename);
                
                if(array_key_exists($templatename, $this->views)) {
                    return $this->_resolve($this->views[$templatename], $loopsidparts, $appearance, array_diff($appearance, $optional));
                }
            }
            else {
                do {
                    $templatename = strtolower($classname);
                    
                    if(!array_key_exists($templatename, $this->views)) {
                        continue;
                    }
                    
                    if($result = $this->_resolve($this->views[$templatename], $loopsidparts, $appearance, array_diff($appearance, $optional))) {
                        return $result;
                    }
                } while($classname = get_parent_class($classname));
            }
        }

        return FALSE;
    }
    
    private function resolveObject($object, $appearance, $optional) {
        //try to find an appearance of the elements page based on the elements loopsid
        if(is_object($object) && $object instanceof ElementInterface) {
            if($result = $this->resolveByPagePath($object, NULL, [], $appearance, $optional)) {
                return $result;
            }
        }
        
        $classname = is_string($object) ? $object : get_class($object);
        
        if($object instanceof CustomizedRenderInterface) {
            if($delegation = $object->delegateRender()) {
                return $this->resolveObject($delegation, $appearance, $optional);
            }
            
            $templatename = str_replace("/", "\\", $object->getTemplateName()) ?: strtolower($classname);
        }
        else {
            $templatename = strtolower($classname);
        }
        
        //try to find a class template for the object
        if($result = $this->resolveClass($templatename, $appearance, $optional)) {
            return $result;
        }

        //try to find a template for the parent class
        if($classname = get_parent_class($classname)) {
            return $this->resolveObject($classname, $appearance, $optional);
        }
        
        //try to render default
        if($templatename != "object") {
            return $this->resolveObject("object", $appearance, $optional);
        }

        return FALSE;
    }
    
    private function scanViewDir($prefix, $path = "") {
        foreach(scandir("$prefix/$path") as $file) {
            if(in_array($file, [".", ".."])) continue;

            if(is_dir("$prefix/$path/$file")) {
                $this->scanViewDir($prefix, "$path/$file");
            }
            else {
                $dot = strrpos($file, ".");
                if(!$dot) continue;
                $ext = substr($file, $dot+1);
                
                $base = substr($file, 0, $dot);

                $parts = explode(".", $base);

                $parts2 = explode("-", array_shift($parts));
                
                $name = ltrim(str_replace("/", "\\", $path)."\\", "\\").array_shift($parts2);

                $value = [$ext, $prefix, ltrim("$path/$file", "/"), $parts];
                
                $rec_set = function(&$array, &$parts, &$parts2) use ($value, &$rec_set) {
                    if($parts2) {
                        $key = "-".array_shift($parts2);
                        if(empty($array[$key])) $array[$key] = [];
                        $rec_set($array[$key], $parts, $parts2);
                        return;
                    }
                    
                    if($parts) {
                        $key = ".".array_shift($parts);
                        if(empty($array[$key])) $array[$key] = [];
                        $rec_set($array[$key], $parts, $parts2);
                    }
                    elseif(!array_key_exists(".", $array)) {
                        $array["."] = $value;
                    }
                };
                
                if(!array_key_exists($name, $this->views)) {
                    $this->views[$name] = [];
                }
                
                $rec_set($this->views[$name], $parts, $parts2);
            }
        }
    }
}