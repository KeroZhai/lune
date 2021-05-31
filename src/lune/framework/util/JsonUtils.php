<?php

namespace lune\framework\util;

class JsonUtils {

    public static function encode($obj, bool $ignoreNull=null) {
        if ($obj === null) {
            return null;
        }
        $json = json_encode($obj, JSON_UNESCAPED_UNICODE);
        $ignoreNull = $ignoreNull === null ? ConfigUtils::getValue("JSON", "non-null", "true") : $ignoreNull;
        if ($ignoreNull) {
            $json = preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $json);
        }
        return $json;
    }
}
