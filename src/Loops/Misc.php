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

namespace Loops;

use ArrayAccess;
use Doctrine\Common\Cache\Cache;
use Loops;
use Loops\ElementInterface;
use Loops\Http\Response;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ReflectionClass;
use ReflectionObject;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionProperty;
use SplFileObject;
use SplStack;
use Throwable;

/**
 * Miscellaneous extra functions that are used within the loops framework
 */
class Misc
{

    /**
     * Get the relative path between 2 folders.
     *
     * @var string $frompath The path from which the relative path should be computed.
     * @var string $topath The target path. If empty or not specified the current working directory will be used.
     * @return string The relative path. "$frompath$result" will resolve to $topath
     */
    public static function getRelativePath($frompath, $topath = "")
    {
        if(empty($topath)) {
            $topath = $frompath;
            $frompath = getcwd();
        }

        if($frompath == ".") $frompath = getcwd();
        if($topath == ".") $topath = getcwd();

        if($frompath[0] != DIRECTORY_SEPARATOR) $frompath = getcwd().DIRECTORY_SEPARATOR.$frompath;
        if($topath[0] != DIRECTORY_SEPARATOR) $topath = getcwd().DIRECTORY_SEPARATOR.$topath;

        $from    = explode(DIRECTORY_SEPARATOR, $frompath); // Folders/File
        $to      = explode(DIRECTORY_SEPARATOR, $topath); // Folders/File
        $relpath = '';

        $i = 0;
        // Find how far the path is the same
        while (isset($from[$i]) && isset($to[$i])) {
            if ($from[$i] != $to[$i])
                break;
            $i++;
        }
        $j = count($from) - 1;
        // Add '..' until the path is the same
        while ($i <= $j) {
            if (!empty($from[$j]))
                $relpath .= '..' . DIRECTORY_SEPARATOR;
            $j--;
        }

        // Go to folder from where it starts differing
        while (isset($to[$i])) {
            if (!empty($to[$i]))
                $relpath .= $to[$i] . DIRECTORY_SEPARATOR;
            $i++;
        }

        // Strip last separator
        return substr($relpath, 0, -1) ?: ".";
    }

    /**
     * Camelizes a string
     *
     * Camelizing in loops means that all underscore characters followed by a lowercase letter are removed.
     * These following letters and the first letter of the string will be transformed to uppercase.
     *
     * For example: camel_case => CamelCase
     *              a_longer_example => ALongerExample
     *
     * @param string $string The input string
     * @return string The camelized string
     */
    public static function camelize($string) {
        return ucfirst(preg_replace_callback("/_[a-z]/", function($match) { return strtoupper($match[0][1]); }, $string));
    }

    /**
     * Transforms a string into underscore format
     *
     * This is the opposite of camelizing
     *
     * For example: CamelCase => camel_case
     *              ALongerExample => a_longer_example
     *
     * @param string $string The input string
     * @return string The camelized string
     */
    public static function underscore($string) {
        return preg_replace_callback("/[A-Z]/", function($match) { return '_'.strtolower($match[0]); }, lcfirst($string));
    }

    /**
     * Outputs a file that can be paused and resumed by the client (by following the partial content spec)
     *
     * Additionally, this function will also set up correct file headers (Content-Type, Content-Disposition, etc)
     * or can return them as an array if $returnheader is set to TRUE.
     * In this case no fileoutput will be generated. You can use Loops\Misc::readpartialfile to actually send the file.
     *
     * The Content-Disposition header will always be set to 'attachment'. 'servefiles' purpose is to provide
     * a resumeable download. If you want to send inline content, use PHPs readfile and header functions instead.
     *
     * $filename or $mimetype can be also set to a boolean. If boolean TRUE, values will be infered from the $file.
     * If set to FALSE, values will NOT be included in the headers.
     * i.e. no filename="..." in Content-Disposition and/or no Content-Type header.
     *
     * If the file and headers have been sent, -1 is returned, thus not genarating additional output.
     * In case of an error, a http status code is generated (4xx, 5xx series) and returned.
     * You can conveniently use this funcion in the action method of loops elements:
     * <code>
     *   use Loops\Misc;
     *
     *   public function action($parameter) {
     *       return Misc::servefile("example.png");
     *   }
     * </code>
     *
     * This function will raise an exeption if there is already data in an outbut buffer or headers were already sent.
     *
     * @todo Do not rely on $_SERVER variable for the partial spec
     *
     * @param string $file The physical location of the file (must be understood by fopen)
     * @param bool|string $filename An alternate filename that is sent in Content-Disposition (must be an UTF-8 string or bool)
     * @param bool|string $mimetype The mimetype that is used for the Content-Type header.
     * @param bool $returnheader If set to TRUE, headers will be returned as an array (no output is generated)
     * @param resource $context The context passed to fopen.
     * @return int|array An http status code or an array of headers
     */
    public static function servefile($file, $filename = TRUE, $mimetype = TRUE, $returnheader = FALSE, $force = FALSE, $context = NULL) {
        if(headers_sent()) {
            throw new Exception("Can't serve file because output headers have already been sent.");
        }

        if(!$force) {
            if(!@file_exists($file)) {
                return 404;
            }

            if(!@is_readable($file)) {
                return 403;
            }
        }


        //detect mimetype - note that spl fileobject does not support this over data urls for some reason
        if($mimetype === TRUE) {
            $mimetype = "application/octet-stream";

            if($finfo = finfo_open(FILEINFO_MIME_TYPE)) {
                if($type = finfo_file($finfo, $file)) {
                    $mimetype = $type;
                }
                finfo_close($finfo);
            }
        }

        //open file
        if(!($file instanceof SplFileObject)) {
            $file = new SplFileObject($file);
        }

        //get size
        $filesize = $file->getSize();

        //get requested range from header
        $range = FALSE;

        if(isset($_SERVER["HTTP_RANGE"])) {
            $range = $_SERVER["HTTP_RANGE"];
        }
        else if(isset($_SERVER["HTTP_CONTENT_RANGE"])) {
            $range = $_SERVER["HTTP_CONTENT_RANGE"];
        }
        else if(function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach($headers as $key => $value) {
                if(strtolower($key) == "range") {
                    $range = $value;
                    break;
                }
            }
        }

        //partial information
        $partial = FALSE;

        if($range) {
            if(strpos($range, '=') === FALSE) {
                return 400;
            }

            list($param, $range) = explode('=', $range, 2);

            if(strtolower(trim($param)) != 'bytes') { // Bad request - range unit is not 'bytes'
                return 400;
            }

            $range = explode(',', $range);
            $range = explode('-', $range[0]); // We only deal with the first requested range

            if(count($range) != 2) { // Bad request - 'bytes' parameter is not valid
                return 400;
            }

            if($range[0] === '') { // First number missing, return last $range[1] bytes
                $offset = $filesize-intval($range[1]);
                if($offset && $offset < $filesize) {
                    $partial = [$offset, $filesize-1];
                }
                else {
                    return 416;
                }
            }
            else if($range[1] === '') { // Second number missing, return from byte $range[0] to end
                $offset = intval($range[0]);
                if($offset < $filesize) {
                    $partial = [$offset, $filesize-1];
                }
                else {
                    return 416;
                }
            }
            else { // Both numbers present, return specific range
                $offset1 = intval($range[0]);
                $offset2 = intval($range[1]);

                if($offset1 < $filesize && $offset2 < $filesize) {
                    $partial = [$offset1, $offset2];
                }
                else {
                    return 416;
                }
            }
        }

        if($filename) {
            if($filename === TRUE) {
                $filename = basename($file);
            }

            // get user-agent from header
            $useragent = FALSE;

            if(isset($_SERVER["HTTP_USER_AGENT"])) {
                $useragent = $_SERVER["HTTP_USER_AGENT"];
            }
            else if(function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                foreach($headers as $key => $value) {
                    if(strtolower($key) == "user-agent") {
                        $useragent = $value;
                        break;
                    }
                }
            }

            //we need to get a cross browser compatible filename for the header. oh boy....
            //lets trust the following stack overflow question:
            //  http://stackoverflow.com/questions/93551/how-to-encode-the-filename-parameter-of-content-disposition-header-in-http
            if($useragent && preg_match("/IE [5678]\./", $useragent)) {
                $filename = " filename=".urlencode($filename);
            }
            else if($useragent && preg_match("/Safari/", $useragent)) {
                $filename = " filename=$filename";
            }
            else if($useragent && preg_match("/[^a-zA-Z0-9_\\.]/", $filename)) {
                $filename = " filename=\"$filename\"; filename*=UTF-8''".urlencode($filename);
            }
            else {
                $filename = " filename=\"$filename\";";
            }
        }

        //create output headers
        $length = $partial ? $partial[1] - $partial[0] + 1 : $filesize;

        $headers = array();
        $headers[] = "Content-Type: $mimetype";
        $headers[] = "Content-Disposition: attachment;$filename";
        $headers[] = "Content-Length: $length";
        $headers[] = "Accept-Ranges: bytes";

        if($partial) {
            $headers[] = "Content-Range: bytes $partial[0]-$partial[1]/$filesize";
        }

        if($returnheader) {
            return $headers;
        }

        //actual output happens from now on

        //set status code and header
        if(!$context) {
            $context = Loops::getCurrentLoops();
        }

        if($partial) {
            $context->response->setStatusCode(206);
        }

        foreach($headers as $line) {
            $context->response->addHeader($line);
        }

        $context->response->setHeader();

        if($filesize) {
            if($partial) {
                $bytes = Misc::readPartialFile($file, $partial[0], $partial[1], $context);
            }
            else {
                $bytes = $file->fpassthru();
            }

            //if no output was generated, the above functions failed and we have to/can recover
            if(!$bytes) {
                //remove previously set headers
                foreach($headers as $line) {
                    $context->response->removeHeader($line);
                }

                return 500;
            }
        }

        //do not generate output
        return -1;
    }

    /**
     * Like PHPs readfile, but the range of the output can be specified
     *
     * <code>
     *   use Loops\Misc;
     *   Misc::readpartialfile("example.png", 500, 1000);
     * </code>
     *
     * Instead of integer ranges an array containing a Content-Range header can be passed.
     * The values found in this header will be used to output the partial file, making it
     * possible to use this function in combination with Loops\Misc::servefile
     *
     * <code>
     *  use Loops\Misc;
     *  $headers = Misc::servefile("example.png", TRUE, TRUE, TRUE);
     *  foreach($headers as $line) {
     *      header($line);
     *  }
     *  Misc::readpartialfile("example.png", $headers);
     *  exit;
     * </code>
     *
     * @param string $file The physical location of the file (must be understood by fopen)
     * @param int|array $from Start file output from this offset or an array containing a Content-Range header line (negative value: n bytes from EOF)
     * @param int $to Stop file output including this offset (0: EOF, negative value: n bytes from EOF)
     * @param resource $context The context passed to fopen.
     */
    public static function readPartialFile($file, $from = 0, $to = 0, $filesize = FALSE, $force = FALSE, $context = NULL) {
        if(!$force) {
            if(!@file_exists($file)) {
                return 404;
            }

            if(!@is_readable($file)) {
                return 403;
            }
        }

        //open file
        if(!($file instanceof SplFileObject)) {
            $file = new SplFileObject($file);
        }

        //get filesize
        $filesize = $file->getSize();

        if(is_array($from)) {
            throw new Exception("Not implemented yet.");
        }

        if($from < 0) {
            $from = $filesize + $from;
        }

        if($to <= 0) {
            $to = $filesize + $to - 1;
        }

        if($from < 0 || $to >= $filesize || $from > $to) {
            throw new Exception("Invalid Range. (filesize: $filesize, from: $from, to: $to)");
        }

        $file->fseek($from);

        $length = $to - $from + 1;

        while($length) {
            $read = ($length > 8192) ? 8192 : $length;
            $length -= $read;
            $content = $file->fread($read);
            if($content === FALSE) {
                return FALSE;
            }
            else {
                print($content);
            }
        }

        return $length;
    }

    /**
     * Flattens an array, preserving keys delimited by a delimiter
     *
     * @param array $array The array that will be flattened
     * @param string $delimiter The used delimiter.
     * @param string $prefix Will be added in front of every key
     * @return array<mixed> The resulting array
     */
    public static function flattenArray($array, $delimiter = '.', $prefix = '') {
        if(!is_array($array)) {
            return $array;
        }

        $result = array();

        foreach($array as $key => $value) {
            if(is_array($value)) {
                $result = array_merge($result, self::flattenArray($value, $delimiter, $prefix.$key.$delimiter));
            }
            else {
                $result[$prefix.$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Unflattens an array by a delimiter.
     *
     * This is particulary useful to pass complex structures via get/post requests from
     * forms.
     * Note: Using a json string should also be considered as an alternative.
     *
     * <code>
     *     use Loops\Misc;
     *
     *     $flatarray = array(
     *         'a'     => 1,
     *         'b.a'   => 2,
     *         'b.b'   => 3
     *     );
     *
     *     Misc::unflattenArray($flatarray);
     *
     *     // $flatarray = array(
     *     //     'a' => 1,
     *     //     'b' => array(
     *     //                'a' => 2,
     *     //                'b' => 3
     *     //            )
     *     //     )
     * </code>
     *
     * @param array $array The array that will be unflattened.
     * @param string $delimiter The used delimiter.
     * @return array The unflattened array
     */
    public static function unflattenArray($array, $delimiter = '.') {
        if(!is_array($array)) {
            return $array;
        }

        $add = array();

        foreach($array as $key => $value) {
            if(strpos($key, $delimiter) === FALSE) {
                continue;
            }

            $add[] = [ explode($delimiter, $key), $value ];

            unset($array[$key]);
        }

        foreach($add as $pair) {
            $current = &$array;

            while(!is_null($key = array_shift($pair[0]))) {
                if(!is_array($current)) {
                    $current = array();
                }

                if(!array_key_exists($key, $current)) {
                    $current[$key] = array();
                }

                $current = &$current[$key];
            }

            $current = $pair[1];
        }

        return $array;
    }

    /**
     * Sets up redirect headers in the response
     *
     * This function returns the desired http status code and thus can be conveniently use.
     * If a loops element is passed, the page path of that element will be used for redirection.
     *
     * <code>
     *     use Loops\Misc;
     *     ...
     *     public function action($parameter) {
     *         return Misc::redirect("http://www.example.com")
     *     }
     * </code>
     *
     * <code>
     *     use Loops\Misc;
     *     ...
     *     public function action($parameter) {
     *         return Misc::redirect($this)
     *     }
     * </code>
     *
     * @param string|object $target The location as a string or a loops element
     * @param integer $status_code The status code for this redirection, defaults to 302 - Temporary redirect
     * @param Loops $loops An alternate Loops context if the default one should not be used
     * @return integer The passed status code
     */
    public static function redirect($target, $status_code = 302, $loops = NULL) {
        if(!$loops) {
            $loops = Loops::getCurrentLoops();
        }

        $config   = $loops->getService("config");
        $request  = $loops->getService("request");
        $response = $loops->getService("response");
        $core     = $loops->getService("web_core");

        if($target instanceof ElementInterface) {
            $target = $core->base_url.$target->getPagePath();
        }

        if(!is_string($target)) {
            throw new Exception("Target must be string or implement 'Loops\ElementInterface'.");
        }

        $response->addHeader("Location: ".$target.$request->getQuery());

        return $status_code;
    }

    /**
     * Clones an object and all properties of it that are also objects.
     * Cross references are kept. This method uses the reflection API, private
     * properties/objects will also be cloned.
     * If an object implements its own __clone method, it will be left untouched.
     *
     * Note:
     * Properties that are not defined in the class and were added during runtime
     * will NOT be deeply cloned. (This is because deepClone uses PHPs reflection API)
     *
     * @param object $object The object that will be deeply cloned.
     * @return object The cloned object
     */
    public static function deepClone($object) {
        static $copy_list = [];

        $hash = spl_object_hash($object);

        if(!empty($copy_list[$hash])) {
            return $copy_list[$hash];
        }

        $top = empty($copy_list);

        $copy_list[$hash] = $result = clone $object;

        //leave object untouched if it took care about its own cloning
        if(!method_exists($object, "__clone")) {
            $r = new ReflectionObject($result);
            foreach($r->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE) as $p) {
                $p->setAccessible(TRUE);
                $v = $p->getValue($object);
                if(!is_object($v)) continue;
                $p->setValue($result, self::deepClone($v));
            }
        }

        if($top) {
            $copy_list = [];
        }

        return $result;
    }

    /**
     * Retrieves the current locale
     *
     * @param int $category The locale category (LC_* constant)
     * @return string The current locale
     */
    public static function getlocale($category = LC_ALL) {
        return setlocale($category, 0);
    }

    /**
     * A (slightly smarter) setlocale function
     *
     * Tries to set the locale. On failure it will try to select
     * a locale that the user might want, based on the output of the
     * 'locale -a' command.
     *
     * @param string $locale The locale string which can be loose
     * @param int $category The locale category (LC_* constant)
     * @return bool TRUE if the locale could be set
     */
    public static function setlocale($locale, $category = LC_ALL) {
        static $localeCache = NULL;

        if(setlocale($category, $locale)) return TRUE;

        //try with .utf8
        if(setlocale($category, "$locale.utf8")) return TRUE;

        //maybe we have the lang-country format
        if(substr_count($locale, "-") == 1) {
            list($lang, $country) = explode("-", $locale);
            $testlocale = strtolower($lang).'_'.strtoupper($country);
            if(setlocale($category, $testlocale)) return TRUE;
            if(setlocale($category, "$testlocale.utf8")) return TRUE;
        }

        //ok, lets get the available locales from the system and check if
        //there is a suitable one available for us
        if($localeCache === NULL) {
            exec("locale -a", $output, $retval);

            $localeCache = $output;

            if($retval) {
                return FALSE;
            }
        }

        foreach(self::$localeCache as $available) {
            $testavailable  = strtolower($available);
            $testlocale     = strtolower(str_replace('-', '_', $locale));
            if($testlocale == substr($testavailable, 0, strlen($testlocale))) {
                if(setlocale($category, $available)) return TRUE;
            }
        }

        //we have tried to do everything but we failed, too bad
        return FALSE;
    }

    /**
     * Recursively unlinks a directory
     *
     * @param string $dir The target directory that will be removed
     * @param bool TRUE on success or FALSE on failure
     */
    public static function recursiveUnlink($dir) {
        $result = TRUE;

        if(is_dir($dir)) {
            foreach(scandir($dir) as $sub) {
                if(in_array($sub, ['.', '..'])) continue;
                $result &= self::recursiveUnlink("$dir/$sub");
            }

            $result &= rmdir($dir);
        }
        elseif(is_file($dir)) {
            $result &= unlink($dir);
        }
        else {
            $result = FALSE;
        }

        return (bool)$result;
    }

    /**
     * Recursively created a directory
     *
     * Like PHPs mkdir but this method will set the recursive flag to TRUE on default
     *
     * @param string $pathname The directory that should be created
     * @param integer $mode Permission that the directories are created with
     * @param string $recursive Set to TRUE on default
     * @param mixed $context The context
     * @return TRUE on success and FALSE on failure
     */
    public static function recursiveMkdir($pathname, $mode = 0777, $recursive = TRUE, $context = NULL) {
        if(is_dir($pathname)) {
            return TRUE;
        }

        if($context) {
            return mkdir($pathname, $mode, $recursive, $context);
        }
        else {
            return mkdir($pathname, $mode, $recursive);
        }
    }

    /**
     * Detects if the last function call (before calling this function) was made recursive.
     *
     * Recursion detection is done by using PHPs debug_backtrace feature. If the last called function
     * appears more than once on the stack TRUE is returned.
     *
     * @param bool If set to true, not only the function/method print must match but also the passed arguments.
     * @return bool Whether the last function was called recursively
     */
    public static function detectRecursion($check_args = FALSE) {
        $stack = debug_backtrace($check_args ? DEBUG_BACKTRACE_PROVIDE_OBJECT : DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);

        array_shift($stack);

        $function = array_shift($stack);

        return in_array($function, $stack, TRUE);
    }

    /**
     * Invokes constructors by assembling arguments from an array with the help of the reflection API.
     *
     * The array values will be passed to the constructor based on the argument names of the constructor signature.
     * Numeric keys can also be used to specify arguments. A number denotes the n-th argument of the constructor.
     * This is especially useful for constructors with a long argument list (where most arguments are defined by default).
     * It can be seen as syntethic sugar to the alternative of passing a single option array as an argument.
     *
     * An exception is thrown if there are missing arguments.
     *
     * Example:
     *
     * <code>
     *     class Test {
     *         public function __construct($arg1, $arg2 = "Default2", $arg3 = "Default3") {
     *             $this->arg1 = $arg1;
     *             $this->arg2 = $arg2;
     *             $this->arg3 = $arg3;
     *         }
     *     }
     * </code>
     *
     * you could instantiate it like this:
     *
     * <code>
     *     // as with the new operator
     *     Misc::reflectionInstance("Test", [ "Value1", "Value2" ]);
     *     // ->arg1 = "Value1"; ->arg2 = "Value2"; ->arg3 = "Default3";
     *
     *     // by specifying argument names directly
     *     Misc::reflectionInstance("Test", [ "Value1", "arg3" => "Value3" ]);
     *     Misc::reflectionInstance("Test", [ "arg1" => "Value1", "arg3" => "Value3" ]);
     *     // ->arg1 = "Value1"; ->arg2 = "Default2"; ->arg3 = "Default3";
     * </code>
     *
     * @param string $classname The name of the class that should be instantiated
     * @param array<mixed> $arguments The arguments that are passed to the constructor
     * @param bool $set_remaining If set to TRUE unused parameters will be set as properties of the class after instantiation.
     * @param false|array<string> If set to an array, only use keys that are listed in this array
     * @param false|array<string> If set to an array, skip keys that are listed in this array
     * @return object The created object instance
     */
    public static function reflectionInstance($classname, $arguments = [], $set_remaining = FALSE, $set_include = FALSE, $set_exclude = FALSE) {
        $reflection = new ReflectionClass($classname);
        if(!$constructor = $reflection->getConstructor()) {
            return new $classname;
        }

        $args = self::getReflectionArgs($constructor, $arguments, $missing);

        if($missing) {
            $parts = [];
            foreach($missing as $key => $name) {
                $parts[] = "'$name' (or '$key')";
            }
            $missing = implode(", ", $parts);
            throw new Exception("Can not create object of type '$classname'. Argument $missing needs to be set.");
        }

        $instance = $reflection->newInstanceArgs($args);

        if($set_remaining) {
            foreach($arguments as $key => $value) {
                if(is_array($set_include) && !in_array($key, $set_include)) {
                    continue;
                }

                if(is_array($set_exclude) && in_array($key, $set_exclude)) {
                    continue;
                }

                $instance->$key = $value;
            }
        }

        return $instance;
    }


    /**
     * Invokes functions by assembling arguments from an array with the help of the reflection API.
     *
     * @todo Better doc here - refer to getReflectionArgs
     */
    public static function reflectionFunction($function, $arguments = []) {
        $method = is_array($function);

        if($method) {
            $reflection = new ReflectionMethod($function[0], $function[1]);
        }
        else {
            $reflection = new ReflectionFunction($function);
        }

        $args = self::getReflectionArgs($reflection, $arguments, $missing);

        if($missing) {
            $name = $reflection->getName();
            $parts = [];
            foreach($missing as $key => $name) {
                $parts[] = "'$name' (or '$key')";
            }
            $missing = implode(", ", $parts);
            throw new Exception("Can not call function '$name'. Argument(s) $missing need to be set.");
        }

        return $method ? $reflection->invokeArgs($function[0], $args) : $reflection->invokeArgs($args);
    }

    /**
     * Assembles an array for call_user_func_array (or similiar) that includes default values obtained by reflection API
     *
     * @todo better doc here
     */
    public static function getReflectionArgs(ReflectionFunctionAbstract $reflection, $arguments = [], &$missing = []) {
        $args    = [];
        $missing = [];

        foreach($reflection->getParameters() as $key => $parameter) {
            if(array_key_exists($key, $arguments)) {
                $args[] = $arguments[$key];
                unset($arguments[$key]);
            }
            elseif(array_key_exists($name = $parameter->getName(), $arguments)) {
                $args[] = $arguments[$name];
                unset($arguments[$name]);
            }
            elseif($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            }
            else {
                $missing[$key] = $name;
            }
        }

        return $missing ? FALSE : $args;
    }

    /**
     * Finds the lastest change in a directory since the last call, if there were any.
     *
     * To implement this feature across requests, the last change time is stored into a Doctrine\Common\Cache\Cache.
     *
     * @params array<string>|string $dirs A single directories or an array of multiple directories that are going to be checked for changes
     * @params Doctrine\Common\Cache\Cache $cache A Doctrine cache object
     * @params string $cache_key The key that has been used to store the last modification time
     * @params DateTime The last modification time or NULL if no file has been changed since the last call of Misc::lastChange
     */
    public static function lastChange($dirs, Cache $cache = NULL, &$cache_key = "") {
        $files = call_user_func_array("array_merge", array_map(function($dir) {
            if(!is_dir($dir)) return [];
            return iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)));
        }, (array)$dirs));

        if(!$files) {
            return NULL;
        }

        $files = array_filter($files, function($file) { return !is_link($file); });

        usort($files, function($a, $b) { return $b->getMTime() - $a->getMTime(); });

        if(!$cache) {
            return $files[0];
        }

        $lastchange = $files[0]->getMTime();

        $cache_key = "Loops-Misc-filesChanged-".implode("-", $dirs);

        if($cache->contains($cache_key)) {
            $mtime = $cache->fetch($cache_key);
        }
        else {
            $mtime = FALSE;
        }

        $cache->save($cache_key, $lastchange);

        if($mtime != $lastchange) {
            return $files[0];
        }

        return NULL;
    }

    /**
     * Shows an exception
     *
     * No dependencies are needed.
     *
     * @todo route this output over application object if possible
     *
     * @param Exception $e The exeption that will be displayed.
     */
    public static function displayException(Throwable $exception, $html = NULL) {
        if($html === NULL) {
            $html = isset($_SERVER["REQUEST_METHOD"]);
        }

        if($html) {
            http_response_code(500);
            ?><!DOCTYPE html>
<html>
<head>
    <title>Whooops!</title>
</head>
<body>
    <h1>Whooops!</h1>
    <p>I caught an exception. Can I keep it?</p>
    <h2><?php echo get_class($exception); ?></h2>
    <p><?php echo $exception->getMessage(); ?></p>
    <h3>Location:</h3>
    <p>
        Line <?php echo $exception->getLine(); ?><br>
        <?php echo $exception->getFile(); ?><br>
    </p>
    <h3>Trace:</h3>
    <pre><?php echo $exception->getTraceAsString(); ?></pre>
</body>
</html><?php
        }
        else {
            $width = 80;

            $message = call_user_func_array("array_merge", array_map(function($a) use ($width) { return str_split($a, $width-4); }, explode("\n", $exception->getMessage())));
            $eline = call_user_func_array("array_merge", array_map(function($a) use ($width) { return str_split($a, $width-12); }, explode("\n", $exception->getLine())));
            $file = call_user_func_array("array_merge", array_map(function($a) use ($width) { return str_split($a, $width-12); }, explode("\n", $exception->getFile())));
            $trace = call_user_func_array("array_merge", array_map(function($a) use ($width) { return str_split($a, $width-6); }, explode("\n", $exception->getTraceAsString())));

            foreach($eline as $key => $value) {
                $eline[$key] = $key ? "      │ $value" : "Line: │ $value";
            }

            foreach($file as $key => $value) {
                $file[$key] = $key ? "      │ $value" : "File: │ $value";
            }

            $len = max(array_map("mb_strlen", array_merge($message, $eline, $file, ["Trace:"], $trace)))+6;

            echo "╔".str_repeat("═", $len-2)."╗\n";
            foreach($message as $line) {
                echo "║ $line".str_repeat(" ", $len - mb_strlen($line) - 4)." ║\n";
            }
            echo "╠═══════╤".str_repeat("═", $len-10)."╣\n";
            foreach($file as $line) {
                echo "║ $line".str_repeat(" ", $len - mb_strlen($line) - 4)." ║\n";
            }
            echo "╟───────┼".str_repeat("─", $len-10)."╢\n";
            foreach($eline as $line) {
                echo "║ $line".str_repeat(" ", $len - mb_strlen($line) - 4)." ║\n";
            }
            echo "╟───────┴".str_repeat("─", $len-10)."╢\n";
            echo "║ Trace:".str_repeat(" ", $len-9)."║\n";
            echo "║".str_repeat(" ", $len-2)."║\n";
            foreach($trace as $line) {
                echo "║   $line".str_repeat(" ", $len - mb_strlen($line) - 5)."║\n";
            }
            echo "╚".str_repeat("═", $len-2)."╝\n";
        }
    }

    /**
     * Gets the full path from relative paths
     *
     * This function will leave already full paths as they are.
     * Parent paths in $path are allowed if $allow_parent is set to true. Otherwise an exception is thrown.
     * If an impossible parent path was passed, an exception will also be thrown.
     *
     * @param string $path The input path
     * @param string|NULL $cwd If path is a relative path, treat it as if is relative to this path. Defaults to current working directory.
     * @param bool $allow_upper Allow parent directories in $path (for example: ../../test/). If not allowed but detected, an exception will be thrown.
     * @return string The input path if it was a full path or a full path based on $cwd + $path otherwise.
     */
    public static function fullPath($path, $cwd = NULL, $allow_parent = FALSE) {
        //unix style full paths
        if(substr($path, 0, 1) == DIRECTORY_SEPARATOR) {
            return $path;
        }

        //windows style full paths
        if(substr($path, 1, 2) == ":\\") {
            return $path;
        }

        //throw exception if there are parent paths
        if(!$allow_parent) {
            $parts = explode(DIRECTORY_SEPARATOR, $path);
            if(array_search("..", $parts) !== FALSE) {
                throw new Exception("Parent directory in path '$path' detected. This is currently not allowed.");
            }
        }

        //get paths (with unresolved parent paths)
        $path = rtrim($cwd ?: getcwd(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path;

        //adjust windows paths for further processing
        if(substr($path, 1, 2) == ":\\") {
            $prefix = substr($path, 0, 3);
            $path = substr($path, 3);
        }
        else {
            $prefix = "";
        }

        //get parts
        $parts = explode(DIRECTORY_SEPARATOR, $path);

        //remove parts in front of .. parts
        $result = [];

        while($parts) {
            $part = array_shift($parts);

            if($part == "..") {
                if(count($result) <= 1) {
                    throw new Exception("'$path' can not exist.");
                }

                array_pop($result);
            }
            else {
                array_push($result, $part);
            }
        }

        //return new assembled path
        return $prefix.implode(DIRECTORY_SEPARATOR, $result);
    }
}
