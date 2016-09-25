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

namespace Loops\Application;

use Exception;
use Throwable;
use Loops\Annotations\Access\ReadOnly;
use Loops\Misc;
use Loops\Application;
use Loops\Http\Request;
use Loops\Http\Response;

class WebApplication extends Application {
    /**
     * @ReadOnly
     */
    protected $url;

    /**
     * @param string The requested URL
     * @param string|NULL $method The request method or NULL if the value from $_SERVER should be used
     * @param array|NULL The GET parameter or NULL if $_GET should be used
     * @param array|NULL The POST parameter or NULL if $_POST should be used
     * @param array|NULL The COOKIE parameter or NULL if $_COOKIE should be used
     * @param array|NULL The FILES parameters or NULL if $_FILES should be used
     * @param array|NULL The SERVER parameters or NULL if $_SERVER should be used
     */
    public function __construct($app_dir, $url, $method = NULL, $get = NULL, $post = NULL, $cookie = NULL, $files = NULL, $server = NULL, $is_ajax = NULL, $cache_dir = "/tmp", $config = "config.php") {
        parent::__construct($app_dir, $cache_dir, $config, FALSE);

        if($method === NULL) {
            $method = @$_SERVER['REQUEST_METHOD'] ?: "GET";
        }

        if($get === NULL) {
            $get = $_GET;
        }

        if($post === NULL) {
            $post = $_POST;
        }

        if($cookie === NULL) {
            $cookie = $_COOKIE;
        }

        if($files === NULL) {
            $files = $_FILES;
        }

        if($server === NULL) {
            $server = $_SERVER;
        }

        if($is_ajax === NULL) {
            $is_ajax = !empty($server['HTTP_X_REQUESTED_WITH']) && strtolower($server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        }

        $request = new Request($method, $get, $post, $cookie, $files, $server, $is_ajax);
        $response = new Response;

        //initialize context
        $this->getLoops()->registerService("request", $request);
        $this->getLoops()->registerService("response", $response);

        //set url for reference
        $this->url = $url;

        //boot
        $this->boot();
    }

    /**
     * Runs the request by by dispaching the request via the web core service.
     * This method will print the resulting output and set http status codes accordingly.
     * Exceptions will be caught and rendered.
     */
    public function run() {
        try {
            $request  = $this->getLoops()->getService("request");
            $response = $this->getLoops()->getService("response");
            $web_core = $this->getLoops()->getService("web_core");

            //dispatch request
            echo $web_core->dispatch($this->url, $request, $response);
        }
        catch(Throwable $exception) {
            http_response_code(500);

            try {
                $renderer = $this->getLoops()->getService("renderer");
                echo $renderer->render($exception);
            }
            catch(Throwable $render_exception) {
                Misc::displayException($exception);
            }
        }
    }
}
