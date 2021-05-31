<?php

namespace lune\framework\annotation;

use lune\framework\annotation\Component;

/**
 * @Annotation
 * @Target({ "PROPERTY" })
 */
final class AliasFor {

    /**
     * @Required
     * @var string
     */
    public $property;

    /**
     *
     * @var string
     */
    public $annotation;
}