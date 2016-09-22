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

class Response {
    public $status_code;
    public $status_message;
    public $extra_header = [];

    public function __construct($status_code = 200) {
        $this->setStatusCode($status_code);
    }

    public function setStatusCode($status_code, $text = "") {
        $this->status_code = $status_code;
        $this->status_message = self::statusCodeText($status_code, $text);
    }

    public function setHeader() {
        if(headers_sent()) {
            return FALSE;
        }

        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : "HTTP/1.1";
        header($protocol." ".$this->status_code." ".$this->status_message, TRUE, $this->status_code);
        array_walk($this->extra_header, "header");
        return TRUE;
    }

    public function addHeader($line, $value = NULL) {
        if($value !== NULL) {
            $line = "$line: $value";
        }
        $this->extra_header[] = $line;
    }

    public function removeHeader($line) {
        $this->extra_header = array_diff($this->extra_header, [$line]);
    }

    public function setJson($charset = "UTF-8") {
        header("Content-Type: text/json; charset=$charset");
    }

    /**
     * Lookup a text representation of HTTP status codes.
     *
     * @var integer $code The HTTP status code
     * @var string $default In case an invalid or unknown HTTP code is given, this string will be returned. (defaults to an empty string)
     * @return string The text representation of the status code.
     */
    public static function statusCodeText($code, $text = "") {
        switch($code) {
            case 100: $text = 'Continue'; break;
            case 101: $text = 'Switching Protocols'; break;
            case 200: $text = 'OK'; break;
            case 201: $text = 'Created'; break;
            case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 305: $text = 'Use Proxy'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 402: $text = 'Payment Required'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 406: $text = 'Not Acceptable'; break;
            case 407: $text = 'Proxy Authentication Required'; break;
            case 408: $text = 'Request Time-out'; break;
            case 409: $text = 'Conflict'; break;
            case 410: $text = 'Gone'; break;
            case 411: $text = 'Length Required'; break;
            case 412: $text = 'Precondition Failed'; break;
            case 413: $text = 'Request Entity Too Large'; break;
            case 414: $text = 'Request-URI Too Large'; break;
            case 415: $text = 'Unsupported Media Type'; break;
            case 416: $text = 'Requested Range Not Satisfiable'; break;
            case 417: $text = 'Expectation Failed'; break;
            case 418: $text = 'I\'m a teapot'; break;
            case 500: $text = 'Internal Server Error'; break;
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
        }

        return $text;
    }
}
