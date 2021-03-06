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

use Loops;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Access\ReadOnly;
use Loops\Service\WebCore;
use ReflectionClass;

/**
 * The page class is a "Loops\Element" that is instantiated by the WebCore service.
 *
 * Although not required from a technical point of view, it is recommended that all Page classes inherit
 * from this class.
 * The main reason is that this class takes care of generating inheritance prone Loops ids of elements.
 * See the documentation of class "Loops\Service\WebCore" for details on when Page instances are created.
 *
 * Example:
 * A page that displays "Hello World!".
 * <code>
 *     namespace Pages;
 *
 *     use Loops\Page;
 *
 *     class Index extends Page {
 *         public $message = "Hello World!";
 *     }
 * </code>
 */
abstract class Page extends Element {
    /**
     * If the page is accessed without parameters, it should be displayed.
     *
     * @var bool Display this Loops element when beeing accessed without parameter (see WebCore service)
     */
    protected $direct_access = TRUE;

    /**
     * @ReadOnly
     * @var array The page parameter that were used to create this page
     */
    protected $parameter;

    /**
     * The Page constructor
     *
     * In most cases, the page is instantiated by the WebCore service.
     * If the Page class has underscore namespaces in its name or is called underscore, then for each found
     * underscore a parameter will be extracted from the accessed url. (see WebCore for details)
     * These parameters are passed to the constructor.
     *
     * @param array $parameter Page parameter for this page, these are typically extracted from the accessed url.
     * @param Loops The Loops context
     */
    public function __construct($parameter = [], Loops $loops = NULL) {
        parent::__construct(NULL, $loops);
        $this->parameter = $parameter;
    }

    /**
     * Take page element inheritance into account when generating the loopsid.
     *
     * Normally the Loops id of a page element is generated by replacing all "\" (backslash) with "-" (dash)
     * of the pages classname.
     * Namespace parts that are underscores, as well if the classname is an underscore will be replaced with
     * the parameters that were used to create the page.
     *
     * If the argument refkey is set, a part of the Loops id of a child element is requested. (See documentation
     * of "Loops\Element")
     * The part of the Loops id is generated and based on which property the child elemement is stored. The
     * declaring classname is used, this may be the name of a parent class.
     *
     *
     * Example:
     * In this example, the Loops id of property $form will not change, regardless which of the three classes are instantiated.
     * <code>
     *     namespace Pages;
     *
     *     use Loops\Page;
     *     use Loops\Annotations\Element\Form;
     *
     *     class Testpage extends Page {
     *         /**
     *          * \@Form
     *          {@*}
     *         protected $form; //Loops id of this element will (always) be "Pages-Testpage-form"
     *     }
     *
     *     class Subtestpage extends Testpage {
     *         /**
     *          * \@Form
     *          {@*}
     *         protected $other_form; //Loops id of this element will be "Pages-Subtestpage-other_form"
     *     }
     *
     *     class _ extends Testpage {
     *         /**
     *          * \@Form
     *          {@*}
     *         protected $other_form; //Loops id of this element will be "Pages-*-other_form" where * is replaced by the first parameter of the argument $parameter in the constructor
     *     }
     * </code>
     *
     * @param
     */
    protected function __getLoopsId($refkey = NULL) {
        $classname = get_class($this);

        if($refkey) {
            $reflection = new ReflectionClass($classname);
            if($reflection->hasProperty($refkey)) {
                $classname = $reflection->getProperty($refkey)->getDeclaringClass()->getName();
            }
        }

        if($page_parameter = $this->parameter) {
            $parts = $newparts = explode("\\", $classname);

            while($parameter = array_shift($page_parameter)) {
                $pos = array_search("_", $parts);

                if($pos == FALSE) {
                    break;
                }

                $parts[$pos] = "";
                $newparts[$pos] = $parameter;
            }

            $loopsid = implode("-", $newparts);
        }
        else {
            $loopsid = str_replace("\\", "-", $classname);
        }

        return $loopsid;
    }

    /**
     * Returns the page path of this page
     *
     * The result is retrieved from the WebCore, if the page was created with parameters they will
     * be taken into account when creating the page path. I.e. underscore namespaces will be replaced
     * by the parameters.
     *
     * Note: If the page is not defined inside the Pages namespace, WebCore::getPagePathFromClassname will
     * return FALSE.
     *
     * Currently page path resolving will only work if an application service is defined. This may be fixed
     * in the future.
     *
     * @return string|FALSE The page path or FALSE if the page is invalid.
     */
    public function getPagePath() {
        return WebCore::getPagePathFromClassname(get_class($this), $this->parameter, $this->getLoops());
    }

    /**
     * Returns TRUE by default
     *
     * This will make the class instantiable by Loops\WebCore
     *
     * @return TRUE
     */
    public static function isPage() {
        return TRUE;
    }
}
