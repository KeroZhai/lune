<?php

namespace app\lune\exception;

class ConfigFileNotFoundException extends \Exception {

    public function __construct() {
        parent::__construct();
        $this->message = "Config file not found in " . $this->getFile() . "at line " . $this->getLine();
    }

}