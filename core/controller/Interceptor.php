<?php

namespace app\core\controller;

use app\core\request\Request;

interface Interceptor {

    const BEFORE = 0;
    const AFTER = 1;

    /**
     * Get the position to invoke this interceptor.<br>
     * Value is <code>Interceptor::BEFORE</code> or <code>Interceptor::AFTER</code>
     * 
     * @return int the position to invoke this interceptor
     */
    function getPosition();

    /**
     * Get the tag to activate this interceptor.
     * 
     * @return string the required tag to activate this interceptor
     */
    function getWithTag();

    /**
     * 
     * 
     */
    function handle(\ReflectionMethod $method, Request $request);

}
