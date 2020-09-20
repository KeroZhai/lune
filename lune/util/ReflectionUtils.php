<?php

namespace app\lune\util;

class ReflectionUtils {

    public static function getClass($objOrClassName) {
        $reflectClass = new \ReflectionClass($objOrClassName);
        return $reflectClass;
    }

    public static function getClassProperty($objOrClassName, $propertyName) {
        return new \ReflectionProperty($objOrClassName, $propertyName);
    }

    public static function getClassProperties($objOrClassName) {
        return ReflectionUtils::getClass($objOrClassName)->getProperties();
    }

    public static function getClassMethod($objOrClassName, $methodName) {
        $reflectMethod = new \ReflectionMethod($objOrClassName, $methodName);
        return $reflectMethod;
    }

    public static function getClassMethods($objOrClassName) {
        return ReflectionUtils::getClass($objOrClassName)->getMethods();
    }

    public static function getClassDocComment($objOrClassName) {
        return ReflectionUtils::getClass($objOrClassName)->getDocComment();
    }

    public static function getMethodDocComment($objOrClassName, $methodName) {
        $reflectMethod = new \ReflectionMethod($objOrClassName, $methodName);
        return $reflectMethod->getDocComment();
    }

    /**
     *
     *
     */
    public static function getDocCommentTag($str, $tag) {
        if (empty($tag)) {
            return "";
        }

        $matches = array();
        preg_match("/".$tag."(.*)(\\r\\n|\\r|\\n)/U", $str, $matches);

        if (isset($matches[1])) {
            return trim($matches[1]);
        }

        return "";
    }
}
