<?php

namespace lune\framework\annotation;

use lune\framework\annotation\Mapping;
use lune\framework\annotation\AliasFor;
use lune\framework\annotation\RequestMethod;

/**
 * @Annotation
 * @Target({ "CLASS", "ANNOTATION" })
 * @Mapping
 */
final class Api {

    /**
     * @AliasFor(property = "path", annotation = Mapping::class)
     * @var string
     */
    public $path;
}