<?php

namespace lune\framework\core\bind;

use RuntimeException;

class DataBindingException extends RuntimeException
{
    public function __construct(string $message) {
        parent::__construct();
        $this->message = $message;
    }
}