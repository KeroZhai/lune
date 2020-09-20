<?php

namespace app\lune\core;

use app\lune\request\Request;
use app\lune\util\CustomSessionHandler;

define("APP_ROOT", dirname($_SERVER["DOCUMENT_ROOT"]));

class App {

    public function __construct() {
        $this->init();
    }

    public function start() {
        $request = Container::get(Request::class);
        $response = (new Dispatcher($request))->dispatch();
        ResponseHandler::handle($response);
    }

    private function init() {
        $this->initErrorReportLevel();
        $this->initAutoload();
        $this->initWarningHandler();
        $this->initThrowableHandler();
        $this->initSession();
    }

    private function initErrorReportLevel() {
        error_reporting(E_ALL & ~E_NOTICE);
    }

    private function initAutoload() {
        spl_autoload_register(function ($className) {
            // map namespace to path
            require APP_ROOT . '/' . implode('/', explode("\\", substr($className, 4))) . ".php";
        }, true);
    }

    private function initWarningHandler() {
        set_error_handler(function ($type, $message, $file, $line) {
            http_response_code(500);
            echo "$message in file $file at line $line";
            exit();
        }, E_WARNING);
    }

    private function initThrowableHandler() {
        set_exception_handler(function (\Throwable $throwable) {
            http_response_code(500);
            echo $throwable->getMessage();
            exit();
        });
    }

    private function initSession() {
        session_set_save_handler(new CustomSessionHandler(), true);
        if (!isset($_SESSION)) {
            session_start();
        }
    }

}
