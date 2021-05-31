<?php

namespace lune\framework\core;

use lune\framework\request\Request;
use lune\framework\request\UploadedFile;
use lune\framework\request\UploadedFiles;
use lune\framework\response\Response;

/**
 * 处理请求
 */
class RequestHandler
{

    public static function handle(object $controller, \ReflectionMethod $method, MappingInfo $mappingInfo, Request $request)
    {
        try {
            $data = RequestHandler::getMergedDataForMethod($request);
            // Invoker::invokeFilterMethods($controller, $methodMappings["filterMethods"], $data);
            // $beforeResult = RequestHandler::handleResult(Invoker::invokeInterceptorMethods($controller, $methodMappings["beforeMethods"], $method, $data));
            // if ($beforeResult === null) {
            //     $apiResult = RequestHandler::handleResult(Invoker::invokeMethod($controller, $method, $data));
            //     RequestHandler::handleResult(Invoker::invokeInterceptorMethods($controller, $methodMappings["afterMethods"], $method, $data));
            //     return $apiResult === null ? Response::ok() : $apiResult;
            // } else {
            //     return $beforeResult;
            // }
            return RequestHandler::handleResult(Invoker::invokeMethod($controller, $method, $data));
        } catch (\Throwable $throwable) {
            return Response::error($throwable->getMessage());
        }
    }

    private static function getMergedDataForMethod(Request $request)
    {
        $queryParams = $request->getQueryParams();
        $body = $request->getBody();
        $mergedData = $queryParams;
        if ($body !== null && is_array($body)) {
            $mergedData = array_merge($queryParams, $body);
        }
        foreach ($_FILES as $key => $file) {
            $mergedData[$key] = is_array($file["name"]) ? RequestHandler::splitUploadedFiles($file) : new UploadedFile($file);
        }
        return $mergedData;
    }

    private static function splitUploadedFiles(array $file, UploadedFiles $files = null)
    {
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

    private static function handleResult($result): Response
    {
        if ($result === null) {
            return Response::ok();
        }
        if ($result instanceof Response) {
            return $result;
        }
        if ($type = RequestHandler::tryGetViewType($result)) {
            if ($content = RequestHandler::getViewContent(APP_ROOT . "/view/$result", $type)) {
                return Response::Builder()->statusCode(Response::OK)->header("Content-Type", "text/html")->body($content)->build();
            } else {
                return Response::notFound();
            }
        }
        return Response::ok($result);
    }

    private static function getViewContent(string $path, string $type)
    {
        if (file_exists($path)) {
            if ($type === "PHP") {
                ob_start();
                include $path;
                return ob_get_clean();
            } else {
                return file_get_contents($path);
            }
        }
        return false;
    }

    private static function tryGetViewType($result)
    {
        if (is_string($result)) {
            if (preg_match("/.*\.php/", $result)) {
                return "PHP";
            } else if (preg_match("/.*\.html/", $result)) {
                return "HTML";
            }
        }
        return false;
    }

}
