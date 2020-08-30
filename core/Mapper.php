<?php

namespace app\core;

use app\core\util\ConfigUtils;
use app\core\util\PathUtils;
use app\core\util\ReflectionUtils;

/**
 * 用于生成和获取路由映射信息
 */
class Mapper {

    private static $mapperLocation = APP_ROOT . "/core/temp/.mapper";

    public static function getMapper(bool $enableCache) {
        $mapper = [];
        if ($enableCache) {
            if (file_exists(Mapper::$mapperLocation)) {
                $mapper = unserialize(file_get_contents(Mapper::$mapperLocation));
            } else {
                $mapper = Mapper::generateMapper();
                Mapper::dump($mapper);
            }
        } else {
            $mapper = Mapper::generateMapper();
        }
        return $mapper;
    }

    /**
     * Scan all controller classes to generate mapper.
     */
    private static function generateMapper() {
        $mapper = [];
        $patterns = ConfigUtils::getArray("Application", "scan-pattern", ["/controller/*"]);
        foreach ($patterns as $pattern) {
            foreach (glob(APP_ROOT . $pattern) as $path) {
                if (is_file($path)) {
                    $className =  PathUtils::pathToNamespace($path, true);
                    Mapper::map($className, $mapper);
                }
            }
        }
        return $mapper;
    }

    /**
     * Store mapping info in an array.
     * 
     */
    private static function map(String $className, Array &$mapper) {
        if ($classDocComment = ReflectionUtils::getClassDocComment($className)) {
            $mappingInfo = ReflectionUtils::getDocCommentTag($classDocComment, "@mapping");
            if (strpos($classDocComment, "@api")) {
                // Is api controller
                if ($mappingInfo) {
                    $mapping = $mappingInfo[0] === "/" ? substr($mappingInfo, 1) : $mappingInfo;
                    Mapper::tryMapping($mapper, "Controller", $mapping, $className);
                } else {
                    echo "Missing class tag @Mapping for controller $className";
                    exit();
                }
            } else if (strpos($classDocComment, "@view")) {
                // Is view controller
                $mappingInfo = $mappingInfo[0] === "/" ? substr($mappingInfo, 1) : $mappingInfo;
                Mapper::tryMapping($mapper, "View", $mappingInfo, $className);
            }
        }
    }

    private static function tryMapping(&$mapper, $type, $mappingInfo, $className) {
        $classMapperKey = $type == "View" ? "V/$mappingInfo" : "C/$mappingInfo";
        if (isset($mapper[$classMapperKey])) {
            $duplicated = $mapper[$classMapperKey]->className;
            echo "$type $className mapping failed, mapping \"/$classMapperKey\" already exists in class $duplicated";
            exit();
        }
        $classMapper = new ClassMapper();
        $classMapper->className = $className;
        $classMapper->methodMappers = Mapper::getMethodMappers($className);
        $mapper[$classMapperKey] = $classMapper;
    }

    /**
     * Save the mapping info to a file.
     */
    private static function dump($mapper) {
        $fp = fopen(Mapper::$mapperLocation, "w");
        if ($fp) {
            fwrite($fp, serialize($mapper));
            fclose($fp);
        } else {
            echo "Failed to write mapping info to file. Please make sure Apache has the write permision.";
            exit();
        }  
    }

    private static function getMethodMappers($className) {
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
                if ($methodMapper = Mapper::getMethodMapper("POST", $methodName, $docComment)) {
                    $post2Method[$methodMapper->pattern] = $methodMapper;
                } else if ($methodMapper = Mapper::getMethodMapper("GET", $methodName, $docComment)) {
                    $get2Method[$methodMapper->pattern] = $methodMapper;
                } else if ($methodMapper = Mapper::getMethodMapper("PUT", $methodName, $docComment)) {
                    $put2Method[$methodMapper->pattern] = $methodMapper;
                } else if ($methodMapper = Mapper::getMethodMapper("PATCH", $methodName, $docComment)) {
                    $patch2Method[$methodMapper->pattern] = $methodMapper;
                } else if ($methodMapper = Mapper::getMethodMapper("DELETE", $methodName, $docComment)) {
                    $delete2Method[$methodMapper->pattern] = $methodMapper;
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

    private static function getMethodMapper(string $method, string $methodName, string $docComment) {
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
            return new MethodMapper($methodName, $pattern, $pathVariableNames);
        }
        return false;
    }

}

class ClassMapper {
    public $className;
    public $methodMappers;
}

class MethodMapper {

    public $methodName;
    public $pattern;
    public $hasPathVariable;
    public $pathVariableNames;

    public function __construct(string $methodName, string $pattern, array $pathVariableNames) {
        $this->methodName = $methodName;
        $this->pattern = $pattern;
        $this->hasPathVariable = count($pathVariableNames) != 0;
        $this->pathVariableNames = $pathVariableNames;
    }

}
