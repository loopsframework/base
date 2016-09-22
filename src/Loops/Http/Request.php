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

namespace Loops\Http;

use Loops\Exception;

class Request {
    private $method;
    private $server;
    private $get;
    private $post;
    private $cookies;
    private $files;

    private $is_ajax;

    private $order = [ "post", "get", "cookies", "server" ];

    public function __construct($method, $get, $post, $cookies, $files, $server, $is_ajax) {
        $this->method = $method;
        $this->get = $get;
        $this->post = $post;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;
        $this->is_ajax = $is_ajax;
    }

    public function isPost() {
        return $this->method == "POST";
    }

    public function method() {
        return $this->method;
    }

    public function post($key = NULL) {
        if($key === NULL) {
            return $this->post;
        }

        if(array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }
    }

    public function get($key = NULL) {
        if($key === NULL) {
            return $this->get;
        }

        if(array_key_exists($key, $this->get)) {
            return $this->get[$key];
        }
    }

    public function getQuery() {
        if(!$this->get) {
            return "";
        }

        return "?".http_build_query($this->get);
    }

    public function cookies($key = NULL) {
        if($key === NULL) {
            return $this->cookies;
        }

        if(array_key_exists($key, $this->cookies)) {
            return $this->cookies[$key];
        }
    }

    public function server($key = NULL) {
        if($key === NULL) {
            return $this->server;
        }

        if(array_key_exists($key, $this->server)) {
            return $this->server[$key];
        }
    }

    public function files($key = NULL) {
        if($key === NULL) {
            return array_map([$this, 'files'], array_keys($this->files));
        }

        if(array_key_exists($key, $this->files)) {
            if(is_array($this->files[$key])) {
                $this->files[$key] = new File($this->files[$key]);
            }

            return $this->files[$key];
        }
    }

    public function __get($key) {
        foreach($this->order as $order) {
            if(array_key_exists($key, $this->$order)) {
                return $this->$order[$key];
            }
        }
    }

    public function isAjax() {
        return $this->is_ajax;
    }
}
