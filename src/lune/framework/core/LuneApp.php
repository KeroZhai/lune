<?php

namespace lune\framework\core;

use lune\framework\request\Request;
use lune\framework\util\CustomSessionHandler;

define("APP_ROOT", dirname($_SERVER["DOCUMENT_ROOT"]));

class LuneApp {

    public function __construct() {
        $this->init();
    }

    public function start() {
        $request = Container::get(Request::class);
        $response = (new Dispatcher())->dispatch($request);
        ResponseHandler::handle($response);
    }

    private function init() {
        $this->initErrorReportLevel();
        $this->initWarningHandler();
        $this->initThrowableHandler();
        $this->initSession();
    }

    private function initErrorReportLevel() {
        error_reporting(E_ALL & ~E_NOTICE);
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
