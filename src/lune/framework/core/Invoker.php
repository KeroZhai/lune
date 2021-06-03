<?php

namespace lune\framework\core;

use lune\framework\core\bind\DataBindingException;
use lune\framework\exception\InitializationException;
use lune\framework\exception\InjectionException;
use lune\framework\request\UploadedFile;
use lune\framework\request\UploadedFiles;
use lune\framework\response\Response;
use lune\framework\util\ReflectionUtils;
use lune\framework\util\StringUtils;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;

/**
 * 方法执行器
 */
class Invoker
{

    public static function invokeMethod(object $object, ReflectionMethod $method, array $data)
    {
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
                } else if (array_key_exists($paramName, $data)) {
                    $paramValue = Invoker::isBuiltin($paramType) ? $data[$paramName] : Invoker::dataToObj($data[$paramName], $paramType);
                } else if (!Invoker::isBuiltin($paramType)) {
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
                    if (in_array($paramName, $paramNamesToInject)) {
                        $paramValue = Invoker::getInstanceToInject($method->getName(), $reflectParam);
                    } else if (array_key_exists($paramName, $data)) {
                        $paramValue = Invoker::isBuiltin($paramType) ? $data[$paramName] : Invoker::dataToObj($data[$paramName], $paramType);
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

    private static function dataToObj($data, ReflectionType $type)
    {
        if (is_array($data) && !empty($data)) {
            // For higher version of PHP, ReflectionType::getName() is avaliable.
            $reflectClass = ReflectionUtils::getClass($type->__toString());
            $instance = $reflectClass->newInstance();
            if (method_exists(ReflectionType::class, "getType")) {
                foreach ($reflectClass->getProperties() as $property) {
                    $propertyName = $property->getName();
                    if (is_array($data) && array_key_exists($propertyName, $data)) {
                        $property->setValue($instance, $data[$propertyName]);
                    }
                }
            } else {
                foreach ($reflectClass->getProperties() as $property) {
                    $propertyName = $property->getName();
                    if (is_array($data) && array_key_exists($propertyName, $data)) {
                        $writeMethodName = "set" . StringUtils::capitalize($propertyName);
                        if ($reflectClass->hasMethod($writeMethodName)) {
                            $writeMethod = $reflectClass->getMethod($writeMethodName);
                            $reflectParams = $writeMethod->getParameters();
                            if (($paramCount = count($reflectParams)) !== 1) {
                                throw new DataBindingException("Expected one parameter for write method of $propertyName, get $paramCount");
                            }
                            $params = [$data[$propertyName]];
                            $writeMethod->invokeArgs($instance, $params);
                        }
                    }
                }
            }
            
            return $instance;
        }
        return null;
    }

    /**
     * Lune 内置类
     */
    const LUNE_BUILTIN = [UploadedFile::class, UploadedFiles::class];

    private static function isBuiltin(ReflectionType $type)
    {
        return $type->isBuiltin() || in_array($type->__toString(), Invoker::LUNE_BUILTIN);
    }

    public static function newInstance(string $className)
    {
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
                    if (in_array($paramName, $paramNamesToInject)) {
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

    public static function getParamNamesToInject(ReflectionMethod $method)
    {
        $paramNamesToInject = [];
        $injectedTagValue = ReflectionUtils::getDocCommentTag($method->getDocComment(), "@injected");
        if ($injectedTagValue) {
            $paramNamesToInject = explode(" ", $injectedTagValue);
        }
        return $paramNamesToInject;
    }

    public static function getInstanceToInject(string $methodName, ReflectionParameter $reflectParam)
    {
        if ($injectionClass = $reflectParam->getType()) {
            if ($injectionClass->isBuiltin()) {
                throw new InjectionException($reflectParam->getName(), $methodName, "Built-in types cannot be injected");
            }
            $injectionClassName = $injectionClass->getName();
            $injectionInstance = null;
            try {
                $injectionInstance = Container::get($injectionClassName);
            } catch (InitializationException $e) {
                throw new InjectionException($reflectParam->getName(), $methodName, $e->getMessage());
            }
            return $injectionInstance;
        } else {
            throw new InjectionException($reflectParam->getName(), $methodName, "Type is required for param to be injected");
        }
    }

    public static function invokeFilterMethods($controller, $filterMethodNames, &$__metadata__)
    {
        foreach ($filterMethodNames as $filterMethodName) {
            $params[] = &$__metadata__;
            $filterMethod = ReflectionUtils::getClassMethod($controller, $filterMethodName);
            $filterMethod->invokeArgs($controller, $params);
        }
    }

    public static function invokeInterceptorMethods($controller, $interceptorMethodNames, ReflectionMethod $method, $data)
    {
        foreach ($interceptorMethodNames as $methodName => $withTag) {
            $execute = true;
            if ($withTag && empty(strpos($method->getDocComment(), $withTag))) {
                $execute = false;
            }
            if ($execute) {
                $data["__metadata__"] = $data;
                $data["__method__"] = $method;
                if ($result = Invoker::invokeMethod($controller, ReflectionUtils::getClassMethod($controller, $methodName), $data)) {
                    return $result;
                }
            }
        }
    }

}
