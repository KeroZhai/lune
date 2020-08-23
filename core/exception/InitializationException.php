<?php

namespace app\core\exception;

class InitializationException extends \Exception {

    public function __construct(string $failedClassName, string $message) {
        parent::__construct();
        $this->message = "Failed to initialize class '$failedClassName': $message";
    }

}
