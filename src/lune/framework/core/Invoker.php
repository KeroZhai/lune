<?php

namespace app\lune\framework\core;

use app\lune\framework\util\ReflectionUtils;
use app\lune\framework\exception\InjectionException;
use app\lune\framework\exception\InitializationException;
use app\lune\framework\request\UploadedFile;
use app\lune\framework\request\UploadedFiles;
use app\lune\framework\response\Response;

/**
 * 方法执行器
 */
class Invoker {

    public static function invokeMethod(object $object, \ReflectionMethod $method, array $data) {
        $paramNamesToInject = Invoker::getParamNamesToInject($method);
        if ($reflectParams = $method->getParameters()) {
            $params = [];
            if (count($reflectParams) == 1 && $reflectParams[0]->hasType()) {
                $reflectParam = $reflectParams[0];
                $paramName = $reflectParam->getName();
                $paramType = $reflectParam->getType();
                $paramValue = null;
                if (in_array($paramName, $paramNamesToInject)) {
                    $paramValue = Invoker::getInstanceToInject($method->getName(), $reflectParam);
                } else if (array_key_exists($reflectParam->getName(), $data)) {
                    $paramValue = Invoker::isBuiltin($paramType) ? $data[$paramName] : Invoker::dataToObj($data[$paramName], $paramType);
                } else {
                    $paramValue = Invoker::dataToObj($data, $paramType);
                }
                if ($paramValue !== null) {
                    $params[] = $paramValue;
                } else {
                    if (!$reflectParams[0]->isDefaultValueAvailable()) {
                        return Response::badRequest("Param required: " . $reflectParams[0]->getName());
                    }
                }
            } else {
                foreach ($reflectParams as $reflectParam) {
                    $paramName = $reflectParam->getName();
                    $paramType = $reflectParam->getType();
                    $paramValue = null;
                    if ($paramType && in_array($paramName, $paramNamesToInject)) {
                        $paramValue = Invoker::getInstanceToInject($method->getName(), $reflectParam);
                    } else if (array_key_exists($paramName, $data)) {
        
                        $paramValue = $paramType ? Invoker::dataToObj($data[$paramName], $paramType) : $data[$paramName];
                    }
                    if ($paramValue !== null) {
                        $params[] = $paramValue;
                    } else if (!$reflectParam->isDefaultValueAvailable()) {
                        return Response::badRequest("Param required: " . $reflectParam->getName());	
                    }
                }
            }
        }
        return $params ? $method->invokeArgs($object, $params) : $method->invoke($object);
    }

    private static function dataToObj($data, \ReflectionType $type) {
        if (is_array($data) && !empty($data)) {
            // For higher version of PHP, ReflectionType::getName() is avaliable.
            $reflectClass = ReflectionUtils::getClass($type->__toString());
            $instance = $reflectClass->newInstance();
            foreach ($reflectClass->getProperties() as $property) {
                $propertyName = $property->getName();
                if (is_array($data) && array_key_exists($propertyName, $data)) {
                    $property->setValue($instance, $data[$propertyName]);
                }
            }
        }        
        return null;
    }

    /**
     * Lune 内置类
     */
    const LUNE_BUILTIN = [UploadedFile::class, UploadedFiles::class];

    private static function isBuiltin(\ReflectionType $type) {
        return $type->isBuiltin() || in_array($type->__toString(), Invoker::LUNE_BUILTIN);
    }

    public static function newInstance(string $className) {
        $class = ReflectionUtils::getClass($className);
        $constructor = $class->getConstructor();
        if ($constructor != null) {
            if (!$constructor->isPublic()) {
                throw new InitializationException($className, "No visible constructor");
            } else if (count($reflectParams = $constructor->getParameters()) > 0) {
                $params = [];
                $paramNamesToInject = Invoker::getParamNamesToInject($constructor);
                foreach ($reflectParams as $reflectParam) {
                    $paramName = $reflectParam->getName();
                    $paramType = $reflectParam->getType();
                    $paramValue = null;
                    if ($paramType && in_array($paramName, $paramNamesToInject)) {
                        $paramValue = Invoker::getInstanceToInject($constructor->getName(), $reflectParam);
                    }
                    if ($paramValue !== null) {
                        $params[] = $paramValue;
                    } else if (!$reflectParam->isDefaultValueAvailable()) {
                        throw new InitializationException($className, "No default value provided for parameter '$paramName'");
                    }
                    return $class->newInstanceArgs($params);
                }
            }
        }
        return $class->newInstance();
    }

    private static function getParamNamesToInject(\ReflectionMethod $method) {
        $paramNamesToInject = [];
        $injectedTagValue = ReflectionUtils::getDocCommentTag($method->getDocComment(), "@injected");
        if ($injectedTagValue) {
            $paramNamesToInject = explode(" ", $injectedTagValue);
        }
        return $paramNamesToInject;
    }

    private static function getInstanceToInject(string $methodName, \ReflectionParameter $reflectParam) {
        $injectionClass = $reflectParam->getClass();
        $injectionClassName = $injectionClass->getName();
        $injectionInstance = null;
        try {
            $injectionInstance = Container::get($injectionClassName);
        } catch (InitializationException $e) {
            throw new InjectionException($reflectParam->getName(), $methodName, $e->getMessage());
        }
        return $injectionInstance;
    }

    public static function invokeFilterMethods($controller, $filterMethodNames, &$__metadata__) {
        foreach ($filterMethodNames as $filterMethodName) {
            $params[] = &$__metadata__;
            $filterMethod = ReflectionUtils::getClassMethod($controller, $filterMethodName);
            $filterMethod->invokeArgs($controller, $params);
        }
    }

    public static function invokeInterceptorMethods($controller, $interceptorMethodNames, \ReflectionMethod $method, $data) {
        foreach ($interceptorMethodNames as $methodName => $withTag) {
            $execute = true;
            if ($withTag && empty(strpos($method->getDocComment(), $withTag))) {
                $execute = false;
            }
            if ($execute) {
                $data["__metadata__"] = $data;
                $data["__method__"] =  $method;
                if ($result = Invoker::invokeMethod($controller, ReflectionUtils::getClassMethod($controller, $methodName), $data)) {
                    return $result;
                }
            }
        }
    }

}
