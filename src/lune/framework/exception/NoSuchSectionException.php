<?php

namespace app\lune\framework\exception;

class NoSuchSectionException extends \Exception {

    public function __construct(string $sectionName) {
        parent::__construct();
        $this->message = "Section [$sectionName] doesn't exist in configuration file";
    }

}
