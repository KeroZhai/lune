<?php

namespace app\lune\framework\response;

use app\lune\framework\util\ConfigUtils;

class Response {

    const CONTINUE = 100;
    const SWITCHING_PROTOCOLS = 101;
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const NON_AUTHORITATIVE_INFORMATION = 203;
    const NO_CONTENT = 204;
    const RESET_CONTENT = 205;
    const PARTIAL_CONTENT = 206;
    const MULTIPLE_CHOICES = 300;
    const MOVED_PERMANENTLY = 301;
    const FOUND = 302;
    const SEE_OTHER = 303;
    const NOT_MODIFIED = 304;
    const USE_PROXY = 305;
    const TEMPORARY_REDIRECT = 307;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const PAYMENT_REQUIRED = 402;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const NOT_ACCEPTABLE = 406;
    const PROXY_AUTHENTICATION_REQUIRED = 407;
    const REQUEST_TIMEOUT = 408;
    const CONFLICT = 409;
    const GONE = 410;
    const LENGTH_REQUIRED = 411;
    const PRECONDITION_FAILED = 412; 
    const REQUEST_ENTITY_TOO_LARGE = 413;
    const REQUEST_URI_TOO_LONG = 414;
    const UNSUPPORTED_MEDIA_TYPE = 415;
    const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const EXPECTATION_FAILED = 417;
    const I_AM_A_TEAPOT = 418;
    const TOO_EARLY = 425;
    const UPGRADE_REQUIRED = 426;
    const PRECONDITION_REQUIRED = 428;
    const TOO_MANY_REQUESTS = 429;
    const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    const UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;
    const BAD_GATEWAY = 502;
    const SERVICE_UNAVAILABLE = 503;
    const GATEWAY_TIMEOUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;

    private $statusCode;
    private $headers;
    private $body;

    public function __construct() {
        $this->status = Response::OK;
        $this->headers = $this->getParsedHeaders();
        $this->setHeader("Content-Type", (ConfigUtils::getBool("Application", "echo-json", true) ? "application/json" : "text/plain") . ";charset=utf-8");
    }

    private function getParsedHeaders() {
        $headers = [];
        foreach (headers_list() as $header) {
            if (preg_match("/^([a-zA-Z\-]*):\s*(.*)$/", $header, $match)) {
                $headers[$match[1]] = explode(",", $match[2]);
            }
        }
        return $headers;
    }

    public function getStatusCode() {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode) {
        $this->statusCode = $statusCode;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function getHeadersAsStringArray() {
        $headers = [];
        foreach ($this->headers as $name => $values) {
            $valuesStr = implode(",", $values);
            $headers[] = "$name: $valuesStr";
        }
        return $headers;
    }

    public function hasHeader(string $name) {
        return array_key_exists($name, $this->headers);
    }

    public function getHeader(string $name) {
        if ($this->hasHeader($name)) {
            return $this->headers[$name];
        }
        return [];
    }

    public function getHeaderAsString(string $name) {
        if ($this->hasHeader($name)) {
            $valuesStr = $this->getHeaderValuesAsString($name);
            return "$name: $valuesStr";
        }
        return "";
    }

    public function getHeaderValuesAsString(string $name) {
        if ($this->hasHeader($name)) {
            $valuesStr = implode(",", $this->headers[$name]);
            return $valuesStr;
        }
        return "";
    }

    public function setHeader(string $name, string ...$values) {
        $this->headers[$name] = $values;
    }

    public function getBody() {
        return $this->body;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public static function builder() {
        return new ResponseBuilder();
    }

    /**
     * @return Response response
     */
    public static function ok($body = null) {
        return (new ResponseBuilder())->statusCode(Response::OK)->body($body)->build();
    }

    /**
     * @return Response response
     */
    public static function badRequest($body = null) {
        return (new ResponseBuilder())->statusCode(Response::BAD_REQUEST)->body($body)->build();
    }

    /**
     * @return Response response
     */
    public static function notFound($body = null) {
        return (new ResponseBuilder())->statusCode(Response::NOT_FOUND)->body($body)->build();
    }

    /**
     * @return Response response
     */
    public static function error($body = null) {
        return (new ResponseBuilder())->statusCode(Response::INTERNAL_SERVER_ERROR)->body($body)->build();
    }
}
