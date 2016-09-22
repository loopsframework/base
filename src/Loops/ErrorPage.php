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
use Loops\Annotations\Access\ReadOnly;
use Loops\Service\WebCore;

/**
 * A loops element that represents an error page. This class is used by the WebCore service.
 *
 * When beeing constructed it will set the status code of the response object (from the loops context).
 */
class ErrorPage extends Page {
    /**
     * @ReadOnly
     */
    protected $status_code;

    /**
     * @param integer $status_code The HTTP status code of the response
     * @param Loops A Loops context to use instead of the default one.
     */
    public function __construct($status_code = 404, Loops $loops = NULL) {
        parent::__construct([], $loops);
        $this->status_code = $status_code;
    }

    /**
     * Returns the page path
     *
     * This method will return the requested url.
     */
    public function getPagePath() {
        $core = $this->getLoops()->getService("web_core");
        $page = $core->page;
        $pagepath = $page === $this ? "" : WebCore::getPagePathFromClassname($page, $core->page_parameter);
        return implode("/", $pagepath ? array_merge([$pagepath], $core->parameter) : $core->parameter);
    }
}
