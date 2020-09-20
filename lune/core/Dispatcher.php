<?php

namespace app\lune\core;

use app\lune\request\Request;
use app\lune\util\ConfigUtils;
use app\lune\response\Response;
use app\lune\util\ReflectionUtils;

/**
 * 请求分发器
 */
final class Dispatcher {

    /**
     * 是否是生产环境
     */
    private $isProd = false;
    private $request = null;
    private $operation;
    private $controllerClassName;
    private $methodMappers;

    public function __construct(Request $request) {
        $this->isProd = ConfigUtils::getValue("Application", "env", "dev") === "prod";
        $this->request = $request;
        $this->initMappers();
    }

    private function initMappers() {
        $mapper = Mapper::getMapper($this->isProd);
        $requestURI = $this->request->getUri();
        if ($requestURI === "") {
            $classMapperKey = "V/";
            $operation = "/";
        } else if (preg_match_all("/\/api\/([0-9a-zA-Z\-_]*)((\/[0-9a-zA-Z\-_]*)*)/", $requestURI, $match)) {
            $classMapperKey = "C/" . $match[1][0];
            $operation = $match[2][0] ? $match[2][0] : "/";
        } else if ($times = preg_match_all("/(\/[0-9a-zA-Z\-_]*)/", $requestURI, $match)) {
            $classMapperKey = "V";
            if ($times == 1) {
                $classMapperKey .= "/";
                $operation = $match[1][0];
            } else {
                $classMapperKey .= $match[1][0];
                $operation = "";
                for ($i = 1; $i < count($match[1]); $i++) {
                    $operation .= $match[1][$i];
                }
            }
        } else {
            return false;
        }
        if (isset($mapper[$classMapperKey])) {
            $classMapper = $mapper[$classMapperKey];
            $this->operation = $operation;
            $this->controllerClassName = $classMapper->className;
            $this->methodMappers = $classMapper->methodMappers;
        }
    }

    /**
     * 将请求分发给对应的控制器, 如果找不到则返回404
     */
    public function dispatch() {
        $controller = $this->getController();
        if ($controller !== null) {
            $methodMapper = $this->getMethodMapper();
            if ($methodMapper !== null) {
                if ($methodMapper === false) {
                    return Response::builder()->statusCode(Response::METHOD_NOT_ALLOWED)->build();
                } else {
                    if ($methodMapper->hasPathVariable) {
                        preg_match_all($methodMapper->pattern, $this->operation, $paramValueMatch);
                        $pathVariableNames = $methodMapper->pathVariableNames;
                        $queryParams = &$this->request->getQueryParams();
                        for ($index = 0; $index < count($pathVariableNames); $index++) {
                            $name = $pathVariableNames[$index];
                            $value = $paramValueMatch[$index + 1][0];
                            if (array_key_exists($name, $queryParams)) {
                                $value .= ", " . $queryParams[$name];
                            }
                            $queryParams[$name] = $value;
                        }
                    }
                    $method = ReflectionUtils::getClassMethod($controller, $methodMapper->methodName);
                    return RequestHandler::handle($controller, $method, $this->methodMappers, $this->request);
                }
            }
        }
        return Response::notFound();
    }

    private function getController() {
        if (!empty($this->controllerClassName)) {
            return Invoker::newInstance($this->controllerClassName);
        }
        return null;
    }

    private function getMethodMapper() {
        $methodMapper = null;
        $method = $this->request->getMethod();
        $operation = $this->operation;
        $methodMappers = $this->methodMappers;
        $getMapper= $this->tryGetMethodMapper($operation, $methodMappers["get2Method"]);
        $postMapper = $this->tryGetMethodMapper($operation, $methodMappers["post2Method"]);
        $putMapper = $this->tryGetMethodMapper($operation, $methodMappers["put2Method"]);
        $patchMapper = $this->tryGetMethodMapper($operation, $methodMappers["patch2Method"]);
        $deleteMapper = $this->tryGetMethodMapper($operation, $methodMappers["delete2Method"]);
        if ($getMapper || $postMapper || $putMapper || $patchMapper || $deleteMapper) {
            if ($getMapper && $method == "GET") {
                $methodMapper = $getMapper;
            } else if ($postMapper && $method == "POST"){
                $methodMapper = $postMapper;
            } else if ($putMapper && $method == "PUT"){
                $methodMapper = $putMapper;
            } else if ($patchMapper && $method == "PATCH"){
                $methodMapper = $patchMapper;
            } else if ($deleteMapper && $method == "DELETE"){
                $methodMapper = $deleteMapper;
            } else {
                $methodMapper = false;
            }
        }
        return $methodMapper;
    }

    private function tryGetMethodMapper(string $operation, array $methodMappers) {
        $matchedMethodMapper = null;
        foreach ($methodMappers as $pattern => $methodMapper) {
            if (!$methodMapper->hasPathVariable) {
                if ($pattern === $operation) {
                    $matchedMethodMapper = $methodMapper;
                    // 直接匹配则立刻break
                    break;
                }
            } else if (preg_match($pattern, $operation)) {
                $matchedMethodMapper = $methodMapper;
                // /page可以直接匹配到/page, 也可以匹配到/{id}, 但前者优先级最高, 所以这里要继续搜索
            } 
        }
        return $matchedMethodMapper;
    }

}
