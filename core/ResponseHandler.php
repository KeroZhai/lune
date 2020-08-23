<?php

namespace app\core;

use app\core\io\File;
use app\core\response\Response;
use app\core\util\JsonUtils;

class ResponseHandler {

    public static function handle(Response $response=null) {
        if ($response !== null) {
            ResponseHandler::emitStatusCode($response->getStatusCode());
            ResponseHandler::emitHeaders($response->getHeadersAsStringArray());
            $body = $response->getBody();
            if ($body instanceof File) {
                if ($body->exists()) {
                    readfile($body->getPath());
                }
            } else {
                if ($response->getHeaderValuesAsString("Content-Type") === "application/json;charset=utf-8") {
                    $body = JsonUtils::encode($body);
                }
                echo $body;
            }
        }
        exit();
    }

    private static function emitHeaders(array $headers) {
        foreach ($headers as $headerString) {
            header($headerString);
        }
    }

    private static function emitStatusCode(int $statusCode) {
        http_response_code($statusCode);
    }

}
