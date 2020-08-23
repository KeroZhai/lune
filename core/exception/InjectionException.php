<?php

namespace app\core\exception;

class InjectionException extends \Exception {

    public function __construct(string $failedParamName, string $failedMethodName, string $message) {
        parent::__construct();
        $this->message = "Injection of parameter '$failedParamName' failed for method '$failedMethodName': $message";
    }

}
