<?php

namespace lune\framework\core\exception;

class MappingException extends \Exception {

    public function __construct(string $message) {
        parent::__construct();
        $this->message = $message;
    }

}
