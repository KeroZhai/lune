<?php

namespace lune\framework\logging;

use lune\framework\util\DateTimeUtils;
use lune\framework\util\StringUtils;

abstract class AbstractLogger extends Logger
{

    private const LOG_FILE_LOCATION = APP_ROOT . DIRECTORY_SEPARATOR . "runtime" . DIRECTORY_SEPARATOR . "app.log";

    protected function log(string $message, string $level)
    {
        $time = $this->currentDateTimeString();
        $className = $this->className;
        $content = "$time [$level] - $className: $message" . PHP_EOL;
        file_put_contents("php://stdout", $content);
        $this->logToFile($content);
    }
    
    protected function logToFile(string $content)
    {
        $fp = fopen(AbstractLogger::LOG_FILE_LOCATION, "a");
        if ($fp) {
            fwrite($fp, $content);
            fclose($fp);
        } else {
            echo "Failed to write mapping info to file. Please make sure Apache has the write permision.";
            exit();
        }  
    }

    // public function info(string $message)
    // {
        
    // }

    // public function debug(string $message)
    // {
        
    // }

    // public function warning(string $message)
    // {
        
    // }

    // public function error(string $message)
    // {
        
    // }

    // abstract function doLogInfo(string $message);

    // abstract function doLogDebug(string $message);

    // abstract function doLogWarning(string $message);

    // abstract function doLogError(string $message);

    
}