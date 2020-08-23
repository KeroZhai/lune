<?php

namespace app\core\controller;

abstract class AbstractInterceptor implements Interceptor {

    public function getPosition() {
        return Interceptor::BEFORE;
    }

    public function getWithTag() {
        return null;
    }

}