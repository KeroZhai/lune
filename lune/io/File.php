<?php

namespace app\lune\io;

class File {

    private $filePath;

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
    }

    public function getPath() {
        return $this->filePath;
    }

    public function getName() {
        return basename($this->filePath);
    }

    public function getSize() {
        if ($this->exists()) {
            return filesize($this->filePath);
        }
        return 0;
    }

    public function exists() {
        return file_exists($this->filePath);
    }

    public function delete() {
        if ($this->exists()) {
            unlink($this->filePath);
            return true;
        }
        return false;
    }

    public function canRead() {
        if ($this->exists()) {
            return is_readable($this->filePath);
        }
        return false;
    }

    public function canWrite() {
        if ($this->exists()) {
            return is_writable($this->filePath);
        }
        return false;
    }

}
