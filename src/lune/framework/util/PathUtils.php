<?php

namespace lune\framework\util;

class PathUtils
{
    public static function getSrcPath(): string
    {
        return PathUtils::implode(APP_ROOT, "src");
    }

    public static function isAbsolutePath(string $path): bool
    {
        return StringUtils::startsWith($path, DIRECTORY_SEPARATOR);
    }

    public static function implode(string ...$segment)
    {
        return implode(DIRECTORY_SEPARATOR, $segment);
    }

    public static function explode(string $paths)
    {
        return explode(DIRECTORY_SEPARATOR, $paths);
    }

    public static function pathToNamespace(string $path, bool $isClass = false)
    {
        if ($isClass) {
            $path = PathUtils::implode(dirname($path), basename($path, ".php"));
        }
        return str_replace(DIRECTORY_SEPARATOR, "\\", str_replace(PathUtils::getSrcPath(), "", $path));
    }

    public static function namespaceToPath(string $namespace)
    {
        PathUtils::implode(...explode("\\", $namespace));
    }
}
