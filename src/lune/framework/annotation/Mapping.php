<?php

namespace lune\framework\annotation;

use lune\framework\annotation\RequestMethod;

/**
 * @Annotation
 * @Target({ "METHOD", "CLASS" })
 */
final class Mapping
{

    /**
     * 
     * @var string
     */
    public $path = "";

    /**
     * 
     *
     * @var string
     */
    public $method = RequestMethod::GET;
}