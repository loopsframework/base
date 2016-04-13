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

use Loops\Exception;
use Loops\Misc;

/**
 * An application that allows to interact with Loops over the command line
 *
 * Functionality is implemented via modules. The Loops admin can be extended
 * by defining classes in the Loops\Application\LoopsAdmin namespace.
 *
 * To interact with Loops, the module and an action has to be specified as
 * arguments from the command line. Modules can define additional flags that
 * will be passed to the according actions.
 *
 * Modules and their actions can also define help messages. These can be shown
 * by the implemented help interface of the LoopsAdmin.
 *
 * The Loops admin is available via the 'loops' command in the bin directory.
 * Usage: loops <module> <action> - execute action
 *        loops help <module> - print help about a module
 *        loops help <module> <action> - print help about an action
 *
 * How to add modules:
 *
 * 1) Define the module
 * To define a module, a class has to be placed in the
 * Loops\Application\LoopsAdmin namespace. The classname should be the
 * camelized name (according to Loops\Misc::camelize) of the module name.
 * 
 * e.g. mymodule    -> Loops\Application\LoopsAdmin\Mymodule
 *      test_module -> Loops\Application\LoopsAdmin\TestModule
 *
 * 2) Define the constructor/Extend from Loops\Object
 * The module should take the Loops context as the first argument.
 * It is strongly recommended to extend from the Loops\Object class as you
 * will also be able to use Loops functionality in your module class.
 * This will also eliminate the need to define the constructor since the
 * Loops\Object class already defines an appropiate one.
 *
 * <code>
 *     namespace Loops\Application\LoopsAdmin;
 *
 *     use Loops\Object;
 *     
 *     class Mymodule extends Object {
 *     }
 * </code>
 *
 * 3) Define help messages
 * A help message should be set by the Loops\Annotations\Admin\Help annotation.
 * It will be displayed at an appropiate place inside a bigger help message.
 * The message should shortly describe what kind of actions it describes.
 *
 * <code>
 *     namespace Loops\Application\LoopsAdmin;
 *
 *     use Loops\Object;
 *     use Loops\Annotations\Admin\Help;
 *
 *     /**
 *      * \@Help("My awesome module that does nothing.")
 *      {@*}
 *     class Mymodule extends Object {
 *     }
 * </code>
 *
 * This help message can be displayed be executing: ./loops help mymodule
 *
 * 4) Define actions
 * Actions are defined as public methods. The methods name should be the
 * camelized name (according to Loops\Misc::camelize) of the action name
 * but with the first letter as lowercase.
 * e.g. action1     -> action1
 *      test_action -> testAction
 *
 * Actions should return an error number or 0 on success. Non integer values
 * will be converted to integer values (no return statement will evaluate to
 * 0).
 * You must also add the \@Action annotation to the method for actions to be
 * recognized. The \@Action annotation should pass help about its
 * functionality. This help message will be displayed at an appropiate place
 * inside a bigger help message when requested.
 * 
 * <code>
 *     namespace Loops\Application\LoopsAdmin;
 *
 *     use Loops\Object;
 *     use Loops\Annotations\Admin\Action;
 *
 *     /**
 *      * \@Help("My awesome module that does nothing.")
 *      {@*}
 *     class Mymodule extends Object {
 *         /**
 *          * \@Action("An action that does nothing.")
 *          {@*}
 *         public function coolAction() {
 *             // Do something
 *             return 0;
 *         }
 *
 *         /**
 *          * \@Action("An action that just fails.")
 *          {@*}
 *         public function lame() {
 *             // Do something
 *             return 1;
 *         }
 *     }
 * </code>
 *
 * This action can be called by executing: ./loops mymodule action1
 *
 * You can throw exceptions in your action method. Exception will be caught
 * and displayed as normal error messages (without backtrace).
 * The application will return an error code with value 1 in this case.
 *
 * The actions help message can be displayed be executing:
 *     ./loops help mymodule cool_action
 *     ./loops help mymodule lame
 *
 * 5) Define flags for your actions
 * You can define a method that may adjust the donatjFlags instance of the
 * Loops\CliApllication before the action is called.
 * For this you have to set the 'init_flags' property of the \@Action
 * annotation to the method name.
 * It should take the donatj\Flags instance as the first argument and should
 * add flags that need to be passed by the user.
 * The method will also be called when help about the action is requested.
 * The added flags are also included in the help message.
 *
 * Flags are passed as arguments for the action method according to their
 * names. To accomplish this, they are passed for processing to
 * Loops\Misc::reflectionFunction. Dashes (-) inside flag names will be
 * replaced with underscores.
 * Therefore the argument names of the action method should match the flag
 * names (with dashes being replaced by underscores).
 * 
 * You also have access to the following arguments names:
 * __arguments -> any additional arguments that were passed on the command
 *                line
 * __module    -> the name of the module
 * __action    -> the name of the action
 *
 * <code>
 *     namespace Loops\Application\LoopsAdmin;
 *
 *     use Loops\Object;
 *     use Loops\Annotations\Admin\Action;
 *     use Loops\Annotations\Admin\Help;
 *
 *     /**
 *      * \@Help("My awesome module that doesn't really do anything.")
 *      {@*}
 *     class Mymodule extends Object {
 *         public function initAction1Flags($flags) {
 *             $flags->string("flag", "default", "My awesome flag.");
 *             $flags->string("flag-with-dashes", "default", "My flag with dash.");
 *         }
 *
 *         /**
 *          * \@Action("Print out passed flags.",init_flags="initAction1Flags")
 *         {@*}
 *         public function action1($flag, $flag_with_dashes, $__arguments) {
 *             echo "Passed flags are '$flag' and '$flag_with_dashes'.\n";
 *             echo "Additional arguments: ".implode(', ', $__arguments)."\n";
 *             return 0;
 *         }
 *     }
 * </code>
 *
 * You can invoke the action as follows:
 *     loops --flag=foo --flag-with-dashes=bar mymodule action1 more arguments
 */
class LoopsAdmin extends CliApplication {
    /**
     * Forwards the call to parsed module name and action
     *
     * See class documentation for details.
     */
    public function exec($arguments) {
        //parse
        $options = $this->parse();

        // check if help was requested
        if(is_integer($options)) {
            return $options;
        }
        
        // extract and remove special vars
        $instance = $options["__instance"];
        $method   = $options["__method"];
        
        unset($options["__instance"]);
        unset($options["__method"]);

        // execute action - print error on failure
        try {
            return (int)Misc::reflectionFunction([ $instance, $method ], self::adjustOptions($options));
        }
        catch(Exception $e) {
            return $this->printError($e->getMessage());
        }
    }
    
    /**
     * replaces dashes with underscore in keys of an array
     */
    private static function adjustOptions(array $options) {
        $result = [];
        
        foreach($options as $key => $value) {
            $result[str_replace("-", "_", $key)] = $value;
        }
        
        return $result;
    }
    
    /**
     * Idents and chunk splits a text
     */
    private function ident($text, $length = 4, $chunk = 76, $break = "\n") {
        $lines = [];
        
        foreach(explode($break, $text) as $line) {
            $text = trim(wordwrap($line, $chunk - $length, $break));
            foreach(explode($break, $text) as $part) {
                $lines[] = str_repeat(" ", $length).$part;
            }
        }
        
        return implode($break, $lines);
    }
    
    /**
     * Implements parsing of a modules action
     *
     * See class documentation for details
     */
    protected function parse($strict = TRUE) {
        if(!$arguments = $this->flags->args()) {
            $message  = "Module not specified.\n";
            $message .= "\n";
            $message .= "To get help type:\n";
            $message .= "\n";
            $message .= "    {$this->command} ".implode(" ", array_merge($this->arguments, [ "help" ]));
            return $this->printError($message);
        }
        
        $module = array_shift($arguments);
        
        // check if we are in help mode
        $is_help = ($module == "help");
        
        if($is_help) {
            if(!$arguments) {
                $modules = [];
                if(class_exists("Loops\Application\LoopsAdmin\Cache")) $modules[] = "cache";
                if(class_exists("Loops\Application\LoopsAdmin\Jobs"))  $modules[] = "jobs";
                
                $message  = "Welcome to the Loops admin.\n";
                $message .= "\n";
                $message .= "You can run Loops internal commands via this interface.\n";
                $message .= "Loops internal commands are grouped into modules.\n";
                $message .= "\n";
                $message .= "Available (and known) modules:\n";
                $message .= "\n";
                $message .= $this->ident(implode(", ", $modules))."\n";
                $message .= "\n";
                $message .= "\n";
                $message .= "To get help about a module:\n";
                $message .= "\n";
                $message .= "    {$this->command} [<flags>...] help <module>\n";
                $message .= "\n";
                $message .= "Each module defines action(s) which are listed on the modules help page.\n";
                $message .= "\n";
                $message .= "\n";
                $message .= "To get help about an action:\n";
                $message .= "\n";
                $message .= "    {$this->command} [<flags>...] help <module> <action>\n";
                $message .= "\n";
                $message .= "\n";
                $message .= "Available flags:";
                return $this->printHelp($message);
            }
            
            // help for this module requested
            $module = array_shift($arguments);
        }
        
        $classname = "Loops\Application\LoopsAdmin\\".Misc::camelize($module);
        
        if(!class_exists($classname)) {
            return $this->printError("Module not found: $module");
        }
        
        $annotations = $this->getLoops()->getService("annotations")->get($classname);
        
        if(!$arguments) {
            if($is_help) {
                if(!$help = $annotations->findFirst("Admin\Help")) {
                    return $this->printError("Module '$module' does not define a help message.");
                }
                
                $actions = [];
                
                foreach($annotations->methods as $method => $method_annotations) {
                    if(!$method_annotations->findFirst("Admin\Action")) continue;
                    $actions[] = Misc::underscore($method);
                }
                
                $message  = "Help for module '$module':\n";
                $message .= "\n";
                $message .= $this->ident($help->help)."\n";
                $message .= "\n";
                $message .= "\n";
                $message .= "Available actions:\n";
                $message .= "\n";
                $message .= $this->ident($actions ? implode(", ", $actions) : "No actions defined.")."\n";
                $message .= "\n";
                $message .= "\n";
                $message .= "To get help about an action:\n";
                $message .= "\n";
                $message .= "    {$this->command} [<flags>...] help $module <action>\n";
                $message .= "\n";
                $message .= "\n";
                $message .= "Available flags:";
                return $this->printHelp($message);
            }
            else {
                return $this->printError("Action not specified for module '$module'.");
            }
        }
        
        $action = array_shift($arguments);
        $method = lcfirst(Misc::camelize($action));
        
        if(empty($annotations->methods->$method) || !$action_annotation = $annotations->methods->$method->findFirst("Admin\Action")) {
            return $this->printError("Module '$module' does not support action: $action");
        }
        
        $instance = new $classname($this->getLoops());
        
        if($action_annotation->init_flags) {
            if(!method_exists($instance, $action_annotation->init_flags)) {
                throw new Exception("Failed to init flags. Method '{$action_annotation->init}' is not defined.");
            }
            
            Misc::reflectionFunction([$instance, $action_annotation->init_flags], [$this->flags]);
        }
        
        if($is_help) {
            if(!$help = $annotations->methods->$method->findFirst("Admin\Help")) {
                return $this->printError("Action '$action' of module '$module' does not define a help message.");
            }

            $message  = "Usage:\n";
            $message .= "\n";
            $message .= "    {$this->command} [<flags>...] $module $action".($action_annotation->arguments ? " {$action_annotation->arguments}" : "")."\n";
            $message .= "\n";
            $message .= "\n";
            $message .= "Help for action '$module $action':\n";
            $message .= "\n";
            $message .= $this->ident($help->help)."\n";
            $message .= "\n";
            $message .= "\n";
            $message .= "Available flags:";
            return $this->printHelp($message);
        }
        
        try {
            $result = $this->flags->parse($this->arguments, !$strict, FALSE);
        }
        catch(Exception $e) {
            $message  = $e->getMessage()."\n";
            $message .= "\n";
            $message .= "To get help about this action:\n";
            $message .= "\n";
            $message .= "    {$this->command} help $module $action";
            return $this->printError($message);
        }
        
        $result = parent::parse();
        
        if(is_integer($result)) {
            $this->printError("test");
            return $result;
        }
        
        $result["__arguments"] = $arguments;
        $result["__module"]    = $module;
        $result["__action"]    = $action;
        
        $result["__instance"]  = $instance;
        $result["__method"]    = $method;
        
        return $result;
    }
}