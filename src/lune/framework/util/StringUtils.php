<?php

namespace app\lune\framework\util;

class StringUtils {


    public static function isEmpty(string $string) {
        return $string === null || $string === "";
    }

    public static function isBlank(string $string) {
        return StringUtils::isEmpty(trim($string));
    }

    public static function isNotEmpty(string $string) {
        return !StringUtils::isEmpty($string);
    }

    public static function isNotBlank(string $string) {
        return !StringUtils::isBlank($string);
    }

    /**
     * 不会过滤空字符串
     */
    public static function split(string $delimiter, string $string, bool $trimString=true) {
        if ($trimString) {
            $result = preg_split("/(\s*$delimiter\s*)/", $string);
            $count = count($result);
            $result[0] = trim($result[0]);
            $result[$count - 1] = trim($result[$count - 1]);
            return $result;
        }
        return explode($delimiter, $string);
    }

}
