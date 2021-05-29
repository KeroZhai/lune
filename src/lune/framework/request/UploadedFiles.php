<?php

namespace app\lune\framework\request;

class UploadedFiles implements \ArrayAccess, \Countable, \Iterator {

    private $position = 0;

    /**
     * UploadedFile数组
     */
    private $files = [];

    public function offsetExists($offset) {
        return isset($this->files[$offset]);
    }

    public function offsetSet($offset, $value) {
        $this->files[$offset] = $value;
    }

    public function append($value) {
        $this->files[] = $value;
    }

    public function offsetGet($offset) {
        return $this->files[$offset];
    }

    public function offsetUnset($offset) {
        unset($this->files[$offset]);
    }

    public function count() {
        return count($this->files);
    }

    public function rewind() {
        $this->position = 0;
    }

    public function current() {
        return $this->files[$this->position];
    }

    public function key() {
        return $this->files;
    }

    public function next() {
        ++$this->position;
    }

    public function valid() {
        return isset($this->files[$this->position]);
    }

}