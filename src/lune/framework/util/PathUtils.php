<?php

namespace app\lune\framework\util;

class PathUtils {


    public static function implode(string ...$segment) {
        return implode('/', $segment);
    }

    public static function explode(string $paths) {
        return explode('/', $paths);
    }

    public static function pathToNamespace(string $path, bool $isClass=false, string $root = APP_ROOT) {
        if ($isClass) {
            $path = PathUtils::implode(dirname($path), basename($path, ".php"));
        }
        return implode("\\", PathUtils::explode(str_replace($root, "app", $path)));
    }

    public static function namespaceToPath(string $namespace) {
        PathUtils::implode(...explode("\\", $namespace));
    }

}