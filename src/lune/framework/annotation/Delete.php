<?php

namespace lune\framework\annotation;

use lune\framework\annotation\Mapping;
use lune\framework\annotation\RequestMethod;
use lune\framework\annotation\AliasFor;

/**
 * @Annotation
 * @Target({ "METHOD", "CLASS" })
 * @Mapping(method = RequestMethod::DELETE)
 */
class Delete
{

    /**
     * @AliasFor(property = "path", annotation = Mapping::class)
     * @var string
     */
    public $path;
}
