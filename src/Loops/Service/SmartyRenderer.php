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
use Loops\Misc;
use Loops\Renderer\RenderPluginInterface;
use Loops\Service;
use Smarty;

class SmartyRenderer extends Service implements RenderPluginInterface {
    public static $shared = FALSE;
    
    /**
     * @ReadOnly
     */
    protected $smarty;
    
    public function __construct($compile_dir = NULL, $template_dir = NULL, $cache_dir = NULL, $plugins_dir = [], $config_dir = [], $disable_security = FALSE, Loops $loops = NULL) {
        parent::__construct($loops);
        
        $loops        = $this->getLoops();
        $application  = $loops->getService("application");
        $renderer     = $loops->getService("renderer");
        $app_dir      = $application->app_dir;
        $cache_dir    = $application->cache_dir;
        
        //adjust directories
        $compile_dir    = Misc::fullPath($compile_dir ?: "renderer_cache/smarty", $cache_dir);
        $template_dir   = array_map(function($path) use ($app_dir) { return Misc::fullPath($path, $app_dir); }, (array)($template_dir ?: $renderer->view_dir));
        $cache_dir      = Misc::fullPath($compile_dir ?: "renderer_cache/smarty_cache", $cache_dir);
        $plugins_dir    = array_map(function($path) use ($app_dir) { return Misc::fullPath($path, $app_dir); }, (array)$plugins_dir);
        $config_dir     = array_map(function($path) use ($app_dir) { return Misc::fullPath($path, $app_dir); }, (array)$config_dir);
        
        //setup smarty
        $this->smarty = new Smarty;
        $this->smarty->setCompileDir($compile_dir);
        $this->smarty->setTemplateDir($template_dir);
        $this->smarty->setCacheDir($cache_dir);
        $this->smarty->addPluginsDir($plugins_dir);
        $this->smarty->setConfigDir($config_dir);
        if($disable_security) {
            $this->smarty->disableSecurity();
        }
        
        //register extra modifier
        $this->smarty->registerPlugin("modifier", "render", [ $loops->getService("renderer"), "render" ]);
        $this->smarty->registerPlugin("modifier", "tr", [ $this, "tr" ]);
    }
    
    /**
     * @todo implement translation
     */
    public function tr($value) {
        return $value;
    }

    public function addVar($key, $value) {
        $this->smarty->assign($key, $value);
    }
    
    public function render($path, $filename) {
        $this->smarty->setTemplateDir(array_unique(array_merge([$path], $this->smarty->getTemplateDir())));
        return $this->smarty->fetch($filename);
    }
}