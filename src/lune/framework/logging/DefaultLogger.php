<?php

namespace lune\framework\logging;

use lune\framework\util\DateTimeUtils;

class DefaultLogger extends AbstractLogger
{

    public function info(string $message)
    {
        $this->log($message, Logger::INFO);
    }

    public function debug(string $message)
    {
        $this->log($message, Logger::DEBUG);
    }

    public function warning(string $message)
    {
        $this->log($message, Logger::WARNING);
    }

    public function error(string $message)
    {
        $this->log($message, Logger::ERROR);
    }
}