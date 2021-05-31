<?php

namespace lune\framework\response;

class ResponseBuilder {

    /**
     * @var Response
     */
    private $response = null;

    public function __construct() {
        $this->response = new Response();
    }

    /**
     * @return ResponseBuilder this
     */
    public function statusCode(int $statusCode) {
        $this->response->setStatusCode($statusCode);
        return $this;
    }

    /**
     * @return ResponseBuilder this
     */
    public function header(string $name, string ...$values) {
        $this->response->setHeader($name, ...$values);
        return $this;
    }

    /**
     * @return ResponseBuilder this
     */
    public function body($body) {
        $this->response->setBody($body);
        return $this;
    }

    /**
     * @return Response The built response
     */
    public function build() {
        return $this->response;
    }

}
