<?php

namespace app\lune\util;

class CustomSessionHandler implements \SessionHandlerInterface {

    private $aliveTime;
    private $savePath;

    public function __construct() {
        $this->aliveTime = ConfigUtils::getValue("Application", "session-alive-time", "1800");
        $this->savePath = APP_ROOT . "/lune/temp/session";
    }

    public function open($savePath, $sessionName) {
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777, true);
        }
        return true;
    }

    private function isExpired($id) {
        foreach (glob("$this->savePath/sess_$id") as $file) {
            if (file_exists($file)) {
                if (filemtime($file) + $this->aliveTime >= time()) {
                    return false;
                } else {
                    unlink($file);
                }
            } 
        }
        return true;
    }

    public function close() {
        return true;
    }

    public function read($id) {
        // 读之前先判断是否过期
        if (!$this->isExpired($id)) {
            return (string) @file_get_contents("$this->savePath/sess_$id");
        }
        return "";
    }

    public function write($id, $data) {
        return file_put_contents("$this->savePath/sess_$id", $data) === false ? false : true;
    }

    public function destroy($id) {
        $file = "$this->savePath/sess_$id";
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    public function gc($maxlifetime) {
        foreach (glob("$this->savePath/sess_*") as $file) {
            if (filemtime($file) + $this->aliveTime < time() && file_exists($file)) {
                unlink($file);
            }
        }

        return true;
    }

}
