<?php

namespace lune\framework\logging;

use lune\framework\util\DateTimeUtils;
use lune\framework\util\StringUtils;

abstract class Logger
{
    protected $className;

    public function __construct(string $className)
    {
        $this->className = $this->trimNamespacedClassNameIfNecessary($className);
    }

    public const INFO = "INFO";
    public const DEBUG = "DEBUG";
    public const WARNING = "WARN";
    public const ERROR = "ERROR";

    public static function getLogger(string $className) {
        return new DefaultLogger($className);
    }

    private function trimNamespacedClassNameIfNecessary(string $className): string
    {
        $paths = StringUtils::split($className, "\\");
        $totalLength = strlen($className);
        if ($totalLength > 50) {
            foreach ($paths as &$path) {
                $this->trimNamespacedClassNameSegmentIfNecessary($path, $totalLength);
            }
        }
        return StringUtils::join($paths, ".");
    }

    private function trimNamespacedClassNameSegmentIfNecessary(&$segment, &$totalLength)
    {
        if ($totalLength > 50) {
            $totalLength -= (strlen($segment) - 1);
            $segment = $segment[0];
        }
    }

    protected function getDateTimeFormat(): string
    {
        return "yyyy-MM-dd HH:mm:ss";
    }

    public function currentDateTimeString(): string
    {
        return DateTimeUtils::getCurrentDateTimeString($this->getDateTimeFormat());
    }

    public abstract function info(string $message);

    public abstract function debug(string $message);

    public abstract function warning(string $message);

    public abstract function error(string $message);
}