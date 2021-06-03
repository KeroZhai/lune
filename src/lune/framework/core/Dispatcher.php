<?php

namespace lune\framework\core;

use lune\framework\request\Request;
use lune\framework\util\ConfigUtils;
use lune\framework\response\Response;
use lune\framework\util\ReflectionUtils;
use ReflectionMethod;

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
    private $registry;

    public function __construct() {
        $this->isProd = ConfigUtils::getValue("Application", "env", "dev") === "prod";
        $this->registry = (new Mapper())->getReigistry($this->isProd);
    }

    /**
     * 将请求分发给对应的控制器, 如果找不到则返回404
     */
    public function dispatch(Request $request)
    {
        $mappingInfo = $this->lookupHandlerMethodMappingInfo($request->getUri());

        if ($mappingInfo === null) {
            return Response::notFound();
        } else {
            $method = $request->getMethod();
            if ($mappingInfo->isRequestMethodAllowed($method)) {
                if ($mappingInfo->hasPathVariables()) {
                    preg_match_all($this->patternToRegx($mappingInfo->getPattern()), $request->getUri(), $paramValueMatch);
                    $pathVariableNames = $mappingInfo->getPathVariableNames();
                    $queryParams = &$request->getQueryParams();
                    for ($index = 0; $index < count($pathVariableNames); $index++) {
                        $name = $pathVariableNames[$index];
                        $value = $paramValueMatch[$index + 1][0];
                        if (array_key_exists($name, $queryParams)) {
                            $value .= ", " . $queryParams[$name];
                        }
                        $queryParams[$name] = $value;
                    }
                }
                $handler = Invoker::newInstance($mappingInfo->getClassName());
                $handlerMethod = ReflectionUtils::getClassMethod($handler, $mappingInfo->getMethodName());
                return RequestHandler::handle($handler, $handlerMethod, $mappingInfo, $request);
            } else {
                return Response::builder()->statusCode(Response::METHOD_NOT_ALLOWED)->build();
            }
        }
    }

    private function lookupHandlerMethodMappingInfo(string $lookupPath): ?MappingInfo
    {
        $matchedMappingInfo = $this->registry->get($lookupPath);
        if ($matchedMappingInfo === null) {
            $this->registry->forEach(function ($pattern, $mappingInfo) use (&$matchedMappingInfo, $lookupPath) {
                if (preg_match($this->patternToRegx($pattern), $lookupPath)) {
                    $matchedMappingInfo = $mappingInfo;
                    // /page可以直接匹配到/page, 也可以匹配到/{id}, 但前者优先级最高, 所以这里要继续搜索
                    return true;
                } 
            });
        }
        return $matchedMappingInfo;
    }

    private function patternToRegx(string $pattern): string
    {
        return "/^" . str_replace("/", "\\/", $pattern) . "\\/?$/";
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
