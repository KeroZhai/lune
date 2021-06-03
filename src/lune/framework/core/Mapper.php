<?php

namespace lune\framework\core;

use Doctrine\Common\Cache\FilesystemCache;
use lune\framework\annotation\Api;
use lune\framework\annotation\Mapping;
use lune\framework\core\registry\InMemoryMappingRegistry;
use lune\framework\core\exception\MappingException;
use lune\framework\logging\Logger;
use lune\framework\util\AnnotationUtils;
use lune\framework\util\ConfigUtils;
use lune\framework\util\PathUtils;
use lune\framework\util\ReflectionUtils;
use lune\framework\util\StringUtils;
use ReflectionClass;
use ReflectionMethod;
use Reflector;

/**
 * 用于生成和获取路由映射信息
 */
class Mapper {

    // private $mappingRegistryDirectory = PathUtils::implode(APP_ROOT, "runtime", ".mappings");
    private $mappingRegistryDirectory = APP_ROOT . DIRECTORY_SEPARATOR . ".runtime" . DIRECTORY_SEPARATOR . ".mappings";
    private $logger;

    public function __construct()
    {
        $this->logger = Logger::getLogger(Mapper::class);
    }

    /**
     * @var InMemoryMappingRegistry
     */
    private $registry;

    public function getReigistry(bool $cacheEnabled) {
        
        $this->registry = new InMemoryMappingRegistry();

        if ($cacheEnabled) {
            $this->registry->load();
        } 
        if ($this->registry->isEmpty()) {
            $this->initHandlerMethods();
            
            if ($cacheEnabled) {
                $this->registry->dump();
            }
        }

        return $this->registry;
    }

    /**
     * Scan all controller classes to generate mapper.
     */
    private function initHandlerMethods() {
        $patterns = ConfigUtils::getArray("Application", "scan-pattern", ["src/controller/*"]);
        foreach ($patterns as $pattern) {
            $pattern = PathUtils::implode(APP_ROOT, "src", $pattern);
            foreach (glob($pattern) as $path) {
                if (is_file($path)) {
                    $className = PathUtils::pathToNamespace($path, true);
                    if ($this->isHandler($className)) {
                        $this->detectHandlerMethods($className);
                    }
                }
            }
        }
    }

    private function isHandler(string $className): bool
    {
        $reflectionClass = new ReflectionClass($className);

        return AnnotationUtils::hasClassAnnotation($reflectionClass, Api::class);
    }

    /**
     * Store mapping info in an array.
     * 
     */
    private function detectHandlerMethods(string $className) {
        $reflectionClass = new ReflectionClass($className);
        $mapping = AnnotationUtils::findMergedAnnotation($reflectionClass, Mapping::class);
        $handlerMappingInfo = new MappingInfo();
        $handlerMappingInfo->setClassName($className);
        $handlerMappingInfo->setPattern($mapping->path);
        $methods = $reflectionClass->getMethods();
        foreach ($methods as $method) {
            if (($mappingInfo = $this->getMappingForMethod($method, $handlerMappingInfo)) !== null) {
                $this->registerHandlerMethod($mappingInfo);
            }
        }
        
    }

    private function getMappingForMethod(ReflectionMethod $method, MappingInfo $handlerMappingInfo): ?MappingInfo
    {
        $mappingInfo = $this->createMappingInfo($method);

        if ($mappingInfo !== null) {
            $mappingInfo->setClassName($handlerMappingInfo->getClassName());
            $joinedPattern = $handlerMappingInfo->getPattern() . "/" . $mappingInfo->getPattern();
            $mappingInfo->setPattern($this->processSlashIfNecessary($joinedPattern));
        }

        return $mappingInfo;
    }

    private function processSlashIfNecessary(string $path): string
    {
        $result = "";
        $segaments = StringUtils::split($path, "/", false);
        foreach ($segaments as $segament) {
            if (StringUtils::isNotBlank($segament)) {
                $result .= "/$segament";
            }
        }
        return $result;
    }

    private function createMappingInfo(ReflectionMethod $reflectionMethod): ?MappingInfo
    {
        $methodMappingInfo = null;
        $mapping = AnnotationUtils::findMergedAnnotation($reflectionMethod, Mapping::class);

        if ($mapping !== null) {
            $path = $mapping->path;
            $pattern = "";
            $prefix = "";
            $pathVariableNames = [];
            if (preg_match("/(\/[0-9a-zA-Z]*)*(\/:[0-9a-zA-Z]*)+/", $path, $match)) {
                // has path variable, at least one
                $times = preg_match_all("/\/:([0-9a-zA-Z]*)/", $path, $paramNameMatch);
                $prefix = $match[1] ?? "";
                $pattern = $prefix;
                for ($i = 0; $i < $times; $i++) { 
                    $pattern .= "/([0-9a-zA-Z\-_]+)";
                    $pathVariableNames[$i] = $paramNameMatch[1][$i];
                }
            } else {
                $pattern = $path;
            }
            $methodMappingInfo = new MappingInfo();
            $methodMappingInfo->setMethodName($reflectionMethod->getName());
            $methodMappingInfo->addAllowedRequestMethod($mapping->method);
            $methodMappingInfo->setPath($path);
            $methodMappingInfo->setPattern($pattern);
            $methodMappingInfo->setPathVariableNames($pathVariableNames);
        }

        return $methodMappingInfo;
        
    }

    private function registerHandlerMethod(MappingInfo $mappingInfo)
    {
        $pattern = $mappingInfo->getPattern();
        $className = $mappingInfo->getClassName();
        $methodName = $mappingInfo->getMethodName();        
        $path = $mappingInfo->getPath();
        $currentAllowedRequestMethod = $mappingInfo->getAllowedRequestMethods()[0];
        if (($existed = $this->registry->get($pattern)) !== null) {
            if ($existed->isRequestMethodAllowed($currentAllowedRequestMethod)) {
                
                $duplicatedClassName = $existed->getClassName();
                $duplicatedMethodName = $existed->getMethodName();
                throw new MappingException("Failed to map path [$currentAllowedRequestMethod] \"$path\" to method $className::$methodName(), duplicated with $duplicatedClassName::$duplicatedMethodName()");
            } else {
                $existed->addAllowedRequestMethod($currentAllowedRequestMethod);
                $this->registry->register($pattern, $existed);
            }
        } else {
            $this->registry->register($pattern, $mappingInfo);
        }
        $this->logger->debug("Mapped path [$currentAllowedRequestMethod] \"$path\" to $className::$methodName()");
    }

    /**
     * Save the mapping info to a file.
     */
    private function dumpRegistry() {
        $fp = fopen($this->mappingRegistryDirectory, "w");
        if ($fp) {
            fwrite($fp, serialize($this->registry));
            fclose($fp);
        } else {
            echo "Failed to write mapping info to file. Please make sure Apache has the write permision.";
            exit();
        }  
    }

    private function getMappingInfos($className) {
        $get2Method = array();
        $post2Method = array();
        $put2Method = array();
        $patch2Method = array();
        $delete2Method = array();
        $filterMethods = array();
        $beforeMethods = array();
        $afterMethods = array();
        $methods = ReflectionUtils::getClassMethods($className);
        foreach ($methods as $method) {			
            if ($docComment = $method->getDocComment()) {
                $methodName = $method->getName();
                if ($MappingInfo = $this->getMappingInfo("POST", $methodName, $docComment)) {
                    $post2Method[$MappingInfo->pattern] = $MappingInfo;
                } else if ($MappingInfo = $this->getMappingInfo("GET", $methodName, $docComment)) {
                    $get2Method[$MappingInfo->pattern] = $MappingInfo;
                } else if ($MappingInfo = $this->getMappingInfo("PUT", $methodName, $docComment)) {
                    $put2Method[$MappingInfo->pattern] = $MappingInfo;
                } else if ($MappingInfo = $this->getMappingInfo("PATCH", $methodName, $docComment)) {
                    $patch2Method[$MappingInfo->pattern] = $MappingInfo;
                } else if ($MappingInfo = $this->getMappingInfo("DELETE", $methodName, $docComment)) {
                    $delete2Method[$MappingInfo->pattern] = $MappingInfo;
                } else if (strpos($docComment, "@filter")) {
                    $filterMethods[] = $methodName;
                } else if (strpos($docComment, "@before")) {
                    $beforeMethods[$methodName] = ReflectionUtils::getDocCommentTag($docComment, "@with");
                } else if (strpos($docComment, "@after")) {
                    $afterMethods[$methodName] = ReflectionUtils::getDocCommentTag($docComment, "@with");
                }
            }
        }
        return [
            "get2Method" => $get2Method,
            "post2Method" => $post2Method,
            "put2Method" => $put2Method,
            "patch2Method" => $patch2Method,
            "delete2Method" => $delete2Method,
            "filterMethods" => $filterMethods,
            "beforeMethods" => $beforeMethods,
            "afterMethods" => $afterMethods,
        ];
    }

    private function getMappingInfo(string $method, string $methodName, string $docComment) {
        switch ($method) {
            case "GET":
                $tag = "@get";
                break;
            case "POST":
                $tag = "@post";
                break;
            case "PUT":
                $tag = "@put";
                break;
            case "PATCH":
                $tag = "@patch";
                break;
            case "DELETE":
                $tag = "@delete";
                break;
            default:
                $tag = "@get";
                break;
        }
        if (strpos($docComment, $tag)) {
            $tagValue = ReflectionUtils::getDocCommentTag($docComment, $tag);
            $tagValue = substr($tagValue, 0, 1) === "/" ? $tagValue : "/$tagValue";
            $pattern = "";
            $prefix = "";
            $pathVariableNames = [];
            if (preg_match("/(\/[0-9a-zA-Z]*)*(\/{[0-9a-zA-Z]*})+/", $tagValue, $match)) {
                // has path variable, at least one
                $times = preg_match_all("/\/{([0-9a-zA-Z]*)}/", $tagValue, $paramNameMatch);
                $prefix = $match[1] ? "\\" . $match[1] : "";
                $pattern = "/$prefix";
                for ($i = 0; $i < $times; $i++) { 
                    $pattern .= "\/([0-9a-zA-Z\-_]+)";
                    $pathVariableNames[$i] = $paramNameMatch[1][$i];
                }
                $pattern .= "/";
            } else {
                $pattern = $tagValue;
            }
            return new MappingInfo($methodName, $pattern, $pathVariableNames);
        }
        return false;
    }

}

class ClassMapping {
    public $className;
    public $MappingInfos;
}

class MappingInfo {

    /**
     *
     * @var array
     */
    private $allowedRequestMethods = [];
    /**
     *
     * @var string
     */
    private $className;
    /**
     *
     * @var string
     */
    private $methodName;
    /**
     *
     * @var string
     */
    private $path;
    /**
     *
     * @var string
     */
    private $pattern;
    /**
     *
     * @var array
     */
    private $pathVariableNames;

    public function setClassName(string $className)
    {
        $this->className = $className;
    }

    public function setMethodName(string $methodName)
    {
        $this->methodName = $methodName;
    }

    public function setPath(string $path)
    {
        $this->path = $path;
    }

    public function setPattern(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public function setPathVariableNames(array $pathVariableNames)
    {
        $this->pathVariableNames = $pathVariableNames;
    }

    public function addAllowedRequestMethod(string $requestMethod)
    {
        $this->allowedRequestMethods[] = $requestMethod;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function hasPathVariables(): bool
    {
        return count($this->getPathVariableNames()) !== 0;
    }

    public function getPathVariableNames(): array
    {
        return $this->pathVariableNames;
    }

    public function getAllowedRequestMethods(): array
    {
        return $this->allowedRequestMethods;
    }

    public function isRequestMethodAllowed(string $requestMethod): bool
    {
        foreach ($this->allowedRequestMethods as $allowedRequestMethod) {
            if ($requestMethod === $allowedRequestMethod) {
                return true;
            }
        }
        return false;
    }
}
