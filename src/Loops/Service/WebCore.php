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

use Loops;
use Loops\Annotations\Access\ReadOnly;
use Loops\ArrayObject;
use Loops\ElementInterface;
use Loops\Exception;
use Loops\Http\Request;
use Loops\Http\Response;
use Loops\Misc;
use Loops\Misc\AccessTrait;
use Loops\Service;
use ReflectionClass;

/**
 * The core object which is responsible for dispatching a web request.
 *
 * @todo evaluate if this features should be defined inside WebApplication
 */
class WebCore extends Service {
    use AccessTrait;
    
    /**
     * @var Loops\Element|string The selected page element or the classname as a string during its construction
     *
     * See method resolvePage for details on how a page is selected
     * 
     * @ReadOnly
     */
    protected $page;
    
    /**
     * @var array<string> The page parameters, which are taken from the url for every _ namespace of the page classname.
     *
     * See method resolvePage for details
     * 
     * @ReadOnly
     */
    protected $page_parameter = [];
    
    /**
     * @var array<string> Remaining parameters from the url
     *
     * See method resolvePage for details
     *
     * @ReadOnly
     */
    protected $parameter = [];
    
    /**
     * @ReadOnly
     */
    protected $error_page;
    
    /**
     * @ReadOnly
     */
    protected $base_url;
    
    public function __construct($base_url = "/", $error_page = "Loops\ErrorPage", Loops $loops = NULL) {
        parent::__construct($loops);
        $this->base_url = $base_url;
        $this->error_page = $error_page;
    }
    
    /**
     * Dispatches a web request to an url and returns the contents to display
     *
     * To generate the output, other resources are used such as the request service.
     *
     * After the page element is resolved (see resolvePage method for detail) its action method is called to determine if the requests could be handled.
     * Depending on the result of the action method the output is generated.
     * Strings will be displayed as they are.
     * Integers will be used to create a Loops\ErrorPage object that is going to be displayed.
     * Returned objects will be displayed unless this is an ajax request, where the page object is going to be displayed instead.
     *
     * The Loopsid of the finally selected object will be set in the response header as "X-Loops-ID"
     *
     * @param string $url The accessed URL
     * @return string The content that should be displayed for this request.
     */
    public function dispatch($url, Request $request, Response $response) {
        $output = $this->resolveOutput($url, $request->isAjax());
        
        if(is_object($output)) {
            if($output instanceof ElementInterface) {
                $response->addHeader("X-Loops-ID", $output->getLoopsId());
            }
            
            $output = $this->display($output, $request, $response);
        }
        
        $response->setHeader();
        
        return $output;
    }
    
    private function resolveOutput($url, $is_ajax) {
        $loops = $this->getLoops();
        
        foreach($this->resolvePage($url) as $set) {
            list($pageclass, $this->page_parameter, $this->parameter) = $set;
            
            $this->page = $pageclass;
            $this->page = Misc::reflectionInstance($pageclass, ["parameter" => $this->page_parameter, "loops" => $loops]);
            
            $action_result = $this->page->action($this->parameter);

            if(is_string($action_result)) {
                return $action_result;
            }

            if($action_result === TRUE) {
                return $this->page;
            }

            if(is_object($action_result)) {
                return $is_ajax ? $action_result : $this->page;
            }

            if(is_integer($action_result)) {
                if($action_result == 404) {
                    continue;
                }
                
                if($action_result <= 0) {
                    return "";
                }

                $loops->getService("response")->setStatusCode($action_result);
                $this->page = Misc::reflectionInstance($this->error_page, [ 'status_code' => $action_result, 'loops' => $loops ]);
                return $this->page;
            }
        }
        
        $this->page_parameter = [];
        $this->parameter = $url == "/" ? [] : explode("/", ltrim($url, "/"));
        $this->page = Misc::reflectionInstance($this->error_page, [ 'status_code' => 404, 'loops' => $loops ]);
        $loops->getService("response")->setStatusCode(404);
        return $this->page;
    }

    /**
     * Counts the number of the needed page parameter given the classname or an object
     *
     * See method resolvePage for details
     *
     * @param string $pageclass
     * @return integer The number of needed page parameter.
     */
    public static function getParameterCount($pageclass) {
        if(is_object($pageclass)) {
            $pageclass = get_class($pageclass);
        }
        $count = array_count_values(explode("\\", $pageclass));
        return array_key_exists("_", $count) ? $count["_"] : 0;
    }
    
    /**
     * Renders an object with the renderer
     */
    private function display($element, Request $request, Response $response) {
        $loops = $this->getLoops();
        $renderer = $loops->getService("renderer");
        
        $appearance = [];

        if($request->isAjax()) {
            $renderer->addExtraAppearance("ajax");
        }

        $renderer->addExtraAppearance((string)$response->status_code);

        return $renderer->render($element, $appearance);
    }
    
    /**
     * Generates the page path for a page element
     *
     * This function also replaces the page parameter placeholders with given arguments
     *
     * @param string|object $classname The classname of the element or an object
     * @param array<string> $page_parameter The page parameter that will be filled into the placeholders
     * @param Loops Use this loops context instead of the default one
     */
    public static function getPagePathFromClassname($classname, $page_parameter = [], Loops $loops = NULL) {
        $count = count($page_parameter);
        
        if(!$routes = self::getRoutes($loops ?: Loops::getCurrentLoops())) {
            return FALSE;
        }
        
        $routes = array_filter($routes, function($value, $key) use ($classname, $count) {
            if($classname != str_replace("*", "_", $value)) {
                return FALSE;
            }
            
            if(substr_count($key, "*") > $count) {
                return FALSE;
            }
            
            return TRUE;
        }, ARRAY_FILTER_USE_BOTH);
        
        if(!$routes) {
            return FALSE;
        }
        
        $pagepath = key($routes);
        
        while($page_parameter && (($pos = strpos($pagepath, "*")) !== FALSE)) {
            $pagepath = substr($pagepath, 0, $pos).array_shift($page_parameter).substr($pagepath, $pos+1);
        }

        return ltrim($pagepath, "/");
    }
    
    /**
     * Generates an array with all available routes 
     */
    private static function getRoutes($loops) {
        $cache = $loops->getService("cache");
        $key = "Loops-WebCore-getRoutes";
        
        if($cache->contains($key)) {
            return $cache->fetch($key);
        }
        
        if(!$loops->hasService("application")) {
            return FALSE;
        }
        
        $classnames = $loops->getService("application")->definedClasses();
    
        $classnames = array_filter($classnames, function($classname) {
            return substr($classname, 0, 6) == "Pages\\";
        });
        
        $routes = [];
        
        foreach($classnames as $classname) {
            if(!in_array("Loops\ElementInterface", class_implements($classname))) {
                continue;
            }
            
            if(!$classname::isPage()) {
                continue;
            }
            
            $reflection = new ReflectionClass($classname);
            if($reflection->isAbstract()) {
                continue;
            }
            
            if(substr($reflection->getFileName(), -strlen($classname)-4, strlen($classname)) != str_replace("\\", "/", $classname)) {
                continue;
            }
            
            $classbase = substr(str_replace("\\", "/", strtolower($classname)), 5);

            if(substr($classbase, -6) == "/index") {
                $classbase = substr($classbase, 0, -5);
            }

            $routes[str_replace("_", "*", substr($classbase, 1))] = $classname;
        }
        
        uksort($routes, function($a, $b) {
            return substr_count($b, "/") - substr_count($a, "/") ?: strlen($b) - strlen($a);
        });
        
        $cache->save($key, $routes);

        return $routes;
    }
    
    /**
     * Find the page object that should be displayed based on the accessed url
     *
     * This method will resolve the url to a page class. It will try to find and autoload
     * the class with the same name as the url (including namespaces) inside the "Pages" namespace.
     * The first letter of every part of the url is capitalized to honor the loops coding standard.
     * Ex: 'subdir/deep/deeper/myclass' -> "Pages\Subdir\Deep\Deeper\Myclass"
     *
     * A trailing '/' in the url tells loops to look for a class named 'Index' inside that namespace.
     * Ex: 'subdir/deep/deeper/myclass/' -> 'Pages\Subdir\Deep\Deeper\Myclass\Index'
     *
     * If no class with that name is found, less deep namespaces will be searched for appropriate classes
     * or Index classes.
     * The above url ('/subdir/deep/deeper/myclass/') will search for page classes in the following order:
     * - Pages\Subdir\Deep\Deeper\Myclass\Index
     * - Pages\Subdir\Deep\Deeper\Myclass
     * - Pages\Subdir\Deep\Deeper\Index
     * - Pages\Subdir\Deep\Deeper
     * - Pages\Subdir\Deep\Index
     * - Pages\Subdir\Deep
     * - Pages\Subdir\Index
     * - Pages\Subdir
     * - Pages\Index
     *
     * All parts of the url that are not part of the classname will be saved in an array as parameters.
     * These parameters will be divided into 'page parameter' and 'action parameter'.
     * If the classname contains namespaces called '_' (a single underbar namespace), the first n parameters will be used as page parameters with n being the number of such namespaces.
     * If the classname is called '_' another parameter will be used as page parameter.
     * These parameters will be passed to the page class constructor.
     * The remaining parameters, if any, will be passed to the action method of that page class while dispatching the request.
     *
     * The classes must be non abstract or they will be taken into account. You can make a page class abstract if other
     * pages inherit from it but should not be displayed directly.
     */
    private function resolvePage($url, $processing = []) {
        $path = ltrim($url, "/");
        $routes = self::getRoutes($this->getLoops());

        foreach($routes as $route => $pageclass) {
            $regexp = "/^".str_replace("\*", "([^\/]+)", preg_quote($route, "/"))."/";

            if(!preg_match($regexp, $path, $match)) {
                continue;
            }

            $page_parameter = array_slice($match, 1);
            
            //check if regexp conditions are defined via annotations
            if($page_parameter) {
                $reqs = $this->getLoops()->getService("annotations")->get($pageclass)->find("PageParameter");
                
                foreach($page_parameter as $parameter) {
                    if($req = array_pop($reqs)) {
                        if($req->regexp) {
                            if(!preg_match($req->regexp, $parameter)) {
                                continue 2;
                            }
                        }
                        
                        if(is_array($req->allow)) {
                            if(!in_array($parameter, $req->allow)) {
                                continue 2;
                            }
                        }
                        
                        if(is_array($req->exclude)) {
                            if(in_array($parameter, $req->exclude)) {
                                continue 2;
                            }
                        }
                        
                        if($req->callback) {
                            if(!call_user_func([$pageclass, $req->callback], $parameter)) {
                                continue 2;
                            }
                        }
                    }
                }
            }
            
            $parameter = ltrim(substr($path, strlen($match[0])), "/");
            $parameter = strlen($parameter) ? array_values(explode("/", $parameter)) : [];

            yield [ $pageclass, $page_parameter, $parameter ];
        }
    }
}