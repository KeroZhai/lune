<?php

namespace app\lune\exception;

class InvalidStateException extends \Exception {

    public function __construct(string $message) {
        parent::__construct();
        $this->message = "Invalid state: $message";
    }

}
