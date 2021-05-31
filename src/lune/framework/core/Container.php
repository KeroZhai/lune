<?php

namespace lune\framework\core;

/**
 * 一个简单的容器
 */
class Container {

    private static $pool = [];

    public static function get(string $className) {
        if (!Container::has($className)) {
            $instance = Invoker::newInstance($className);
            Container::$pool[$className] = $instance;
        }
        return Container::$pool[$className];
    }

    public static function has(string $className) {
        return array_key_exists($className, Container::$pool);
    }

}
