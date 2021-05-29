<?php

namespace app\lune\framework\request;

class Request {

    private $uri;
    private $method;
    private $headers;
    private $queryParams;
    private $body;
    private $cookieParams;
    private $serverParams;
    private $uploadedFiles;


    public function __construct() {
        $this->initUri();
        $this->initMethod();
        $this->initServerParams();
        $this->initHeaders();
        $this->initQueryParams();
        $this->initBody();
        $this->initCooikeParams();
        $this->initUploadedFiles();
    }

    private function initUri() {
        $this->uri = substr($_SERVER["REQUEST_URI"], -1) == "/" ? substr($_SERVER["REQUEST_URI"], 0 ,-1) : $_SERVER["REQUEST_URI"];
    }

    private function initMethod() {
        $this->method = $_SERVER['REQUEST_METHOD'];
    }

    private function initServerParams() {
        $this->serverParams = $_SERVER;
    }

    private function initHeaders() {
        // getallheaders() 只适用于Apache
        $headers = [];
        foreach (getallheaders() as $name => $value) {
            $headers[$name] = explode(", ", $value);
        }
        $this->headers = $headers;
    }

    private function initQueryParams() {
        $this->queryParams = $_GET;
    }

    private function initBody() {
        $body = null;
        if ($this->getMethod() !== "GET") {
            if (empty($_POST)) {
                // 为空可能为raw或是非POST请求
                if ($raw = file_get_contents("php://input")) {
                    $body = $this->getParsedRawData($raw);
                }
            } else {
                $body = $_POST;
            }
        }
        $this->body = $body;
    }

    private function getParsedRawData(string $raw) {
        $contentType = $this->getHeaders()["Content-Type"][0];
        $parsedRawData = [];
        if (stripos($contentType, "form-data")) {
            // 解析form-data
            $list = explode("\r\n", $raw);
            foreach ($list as $value){
                if ($value){
                    if (strstr($value, '--'))
                        continue;
                    if (strpos($value, '-')) {
                        $key = str_replace('"', '', strchr($value, '"'));
                        continue;
                    }
                    if ($value) {
                        $parsedRawData[$key] = $value;	
                    }
                }
            }
        } else if (stripos($contentType, "x-www-form-urlencoded")) {
            parse_str($raw, $parsedRawData);
        } else if (stripos($contentType, "application/json")) {
            $json_data = json_decode($raw, true);
            if ($json_data !== null && is_array($json_data)) {
                foreach ($json_data as $key => $value) {
                    $parsedRawData[$key] = $value;
                }
            }
        }
        return empty($parsedRawData) ? $raw : $parsedRawData;
    }

    private static function splitUploadedFiles(array $file, UploadedFiles $files = null) {
        $temp = [];
        if ($files === null) {
            $files = new UploadedFiles();
        }
        for ($i = 0; $i < count($file["name"]); $i++) {
            $temp["tmp_name"] = $file["tmp_name"][$i];
            $temp["name"] = $file["name"][$i];
            $temp["type"] = $file["type"][$i];
            $temp["size"] = $file["size"][$i];
            $temp["error"] = $file["error"][$i];
            $files->append(new UploadedFile($temp));
        }
        return $files;
    }

    private function initCooikeParams() {
        $this->cookieParams = $_COOKIE;
    }

    private function initUploadedFiles() {
        $files = new UploadedFiles();
        foreach ($_FILES as $file) {
            if (is_array($file["name"])) {
                Request::splitUploadedFiles($file, $files);
            } else {
                $files[] = new UploadedFile($file);
            }
        }
        $this->uploadedFiles = $files;
    }
    
    public function getUri() {
        return $this->uri;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getServerParams() {
        return $this->serverParams;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function &getQueryParams() {
        return $this->queryParams;
    }

    public function &getBody() {
        return $this->body;
    }

    public function getCookieParams() {
        return $this->cookieParams;
    }

    public function getUploadedFiles() {
        return $this->uploadedFiles;
    }

}
