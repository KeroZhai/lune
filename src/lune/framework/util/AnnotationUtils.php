<?php

namespace lune\framework\util;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\FilesystemCache;
use lune\framework\annotation\AliasFor;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;

AnnotationRegistry::registerLoader("class_exists");

class AnnotationUtils
{

    private static $reader;

    private static function getReader(): CachedReader
    {
        if (AnnotationUtils::$reader === null) {
            AnnotationUtils::$reader = new CachedReader(new AnnotationReader(), new FilesystemCache(APP_ROOT . DIRECTORY_SEPARATOR . "runtime" . DIRECTORY_SEPARATOR . ".annotations"), true);
        }
        return AnnotationUtils::$reader;
    }

    public static function isAnnotation(ReflectionClass $class): bool
    {
        return strpos($class->getDocComment(), '@Annotation') !== false;
    }

    public static function hasClassAnnotation(ReflectionClass $annotatedClass, string $annotationClassName, bool $recursive = false): bool
    {
        return AnnotationUtils::getClassAnnotation($annotatedClass, $annotationClassName, $recursive) !== null;
    }

    public static function getClassAnnotation(ReflectionClass $annotatedClass, string $annotationClassName, bool $recursive = false): ?object
    {
        $annotations = AnnotationUtils::getClassAnnotations($annotatedClass, $annotationClassName, $recursive);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationClassName) {
                return $annotation;
            }
        }

        return null;
    }

    public static function getClassAnnotations(ReflectionClass $annotatedClass, bool $recursive = false, $debug = false): array
    {
        $annotations = AnnotationUtils::getReader()->getClassAnnotations($annotatedClass);
        
        if ($recursive) {
            foreach ($annotations as $annotation) {
                $annotations = array_merge($annotations, AnnotationUtils::getClassAnnotations(new ReflectionClass($annotation), true));
            }
        }

        return $annotations;
    }

    public static function hasMethodAnnotation(ReflectionMethod $annotatedMethod, string $annotationClassName, bool $recursive = false): bool
    {
        return AnnotationUtils::getMethodAnnotation($annotatedMethod, $annotationClassName, $recursive) !== null;
    }

    public static function getMethodAnnotation(ReflectionMethod $annotatedMethod, string $annotationClassName, bool $recursive = false): ?object
    {
        $annotations = AnnotationUtils::getMethodAnnotations($annotatedMethod, $annotationClassName, $recursive);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationClassName) {
                return $annotation;
            }
        }

        return null;
    }

    public static function getMethodAnnotations(ReflectionMethod $annotatedMethod, bool $recursive = false): array
    {
        

        $annotations = AnnotationUtils::getReader()->getMethodAnnotations($annotatedMethod);

        if ($recursive) {
            foreach ($annotations as $annotation) {
                $annotations = array_merge($annotations, AnnotationUtils::getClassAnnotations(new ReflectionClass($annotation), true));
            }
        }

        return $annotations;
    }

    public static function hasPropertyAnnotation(ReflectionProperty $annotatedProperty, string $annotationClassName, bool $recursive = false): bool
    {
        return AnnotationUtils::getPropertyAnnotation($annotatedProperty, $annotationClassName, $recursive) !== null;
    }

    public static function getPropertyAnnotation(ReflectionProperty $annotatedProperty, string $annotationClassName, bool $recursive = false): ?object
    {
        $annotations = AnnotationUtils::getPropertyAnnotations($annotatedProperty, $annotationClassName, $recursive);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationClassName) {
                return $annotation;
            }
        }

        return null;
    }

    public static function getPropertyAnnotations(ReflectionProperty $annotatedProperty, bool $recursive = false): array
    {
        $annotations = AnnotationUtils::getReader()->getPropertyAnnotations($annotatedProperty);

        if ($recursive) {
            foreach ($annotations as $annotation) {
                array_merge($annotations, AnnotationUtils::getClassAnnotations(new ReflectionClass($annotation), true));
            }
        }

        return $annotations;
    }

    public static function getAnnotation(Reflector $annotatedElement, string $annotationClassName): ?object
    {
        if ($annotatedElement instanceof ReflectionClass) {
            return AnnotationUtils::getClassAnnotation($annotatedElement, $annotationClassName);
        } else if ($annotatedElement instanceof ReflectionMethod) {
            return AnnotationUtils::getMethodAnnotation($annotatedElement, $annotationClassName);
        } else if ($annotatedElement instanceof ReflectionProperty) {
            return AnnotationUtils::getPropertyAnnotation($annotatedElement, $annotationClassName);
        }
    }

    public static function findAnnotation(Reflector $annotatedElement, string $annotationClassName): ?object
    {
        if ($annotatedElement instanceof ReflectionClass) {
            return AnnotationUtils::getClassAnnotation($annotatedElement, $annotationClassName, true);
        } else if ($annotatedElement instanceof ReflectionMethod) {
            return AnnotationUtils::getMethodAnnotation($annotatedElement, $annotationClassName, true);
        } else if ($annotatedElement instanceof ReflectionProperty) {
            return AnnotationUtils::getPropertyAnnotation($annotatedElement, $annotationClassName, true);
        }
    }

    public static function getAnnotations(Reflector $annotatedElement): ?array
    {
        if ($annotatedElement instanceof ReflectionClass) {
            return AnnotationUtils::getClassAnnotations($annotatedElement);
        } else if ($annotatedElement instanceof ReflectionMethod) {
            return AnnotationUtils::getMethodAnnotations($annotatedElement);
        } else if ($annotatedElement instanceof ReflectionProperty) {
            return AnnotationUtils::getPropertyAnnotations($annotatedElement);
        }
    }

    public static function findAnnotations(Reflector $annotatedElement): ?array
    {
        if ($annotatedElement instanceof ReflectionClass) {
            return AnnotationUtils::getClassAnnotations($annotatedElement, true);
        } else if ($annotatedElement instanceof ReflectionMethod) {
            return AnnotationUtils::getMethodAnnotations($annotatedElement, true);
        } else if ($annotatedElement instanceof ReflectionProperty) {
            return AnnotationUtils::getPropertyAnnotations($annotatedElement, true);
        }
    }

    // TODO 处理级联 AliasFor
    public static function findMergedAnnotation(Reflector $annotatedElement, string $annotationClassName): ?object
    {
        $annotations = AnnotationUtils::findAnnotations($annotatedElement);

        $mergedAnnotation = null;

        foreach ($annotations as $annotation) {
            $lowerLevelAnnotation = null;
            if ($annotation instanceof $annotationClassName) {
                $lowerLevelAnnotation = AnnotationUtils::copyAnnotation($annotation); // 避免修改原注解对象
            } else {
                $reflectionClass = new ReflectionClass($annotation);
                if (($lowerLevelAnnotation = AnnotationUtils::getClassAnnotation($reflectionClass, $annotationClassName)) !== null) {
                    $lowerLevelAnnotation = AnnotationUtils::copyAnnotation($lowerLevelAnnotation); // 避免修改原注解对象
                    $properties = $reflectionClass->getProperties();
                    foreach ($properties as $property) {
                        $aliasFor = AnnotationUtils::getPropertyAnnotation($property, AliasFor::class);
                        if ($aliasFor !== null && $aliasFor->annotation === $annotationClassName) {
                            $propertyName = $aliasFor->property;
                            if (($value = $property->getValue($annotation)) !== null) {
                                $lowerLevelAnnotation->$propertyName = $value;
                            }
                        }
                    }
                }
                
            }
            if ($lowerLevelAnnotation !== null) {
                if ($mergedAnnotation !== null) {
                    AnnotationUtils::mergeAnnotations($lowerLevelAnnotation, $mergedAnnotation);
                }
                $mergedAnnotation = $lowerLevelAnnotation;
            }
        }
        return $mergedAnnotation;
    }

    private static function mergeAnnotations(object $annotation, object $other, bool $inPlace = true): object
    {
        $reflectionClass = new ReflectionClass($annotation);
        if (!$inPlace) {
            $annotation = AnnotationUtils::copyAnnotation($annotation);
        }
        if ($reflectionClass->isInstance($other)) {
            foreach ($other as $propertyName => $propertyValue) {
                if ($propertyValue !== null) {
                    $annotation->$propertyName = $propertyValue;
                }
            }
        }
        return $annotation;
    }

    private static function copyAnnotation(object $annotation): object
    {
        $reflectionClass = new ReflectionClass($annotation);
        $copy = $reflectionClass->newInstance();
        foreach ($annotation as $propertyName => $propertyValue) {
            if ($propertyValue !== null) {
                $copy->$propertyName = $propertyValue;
            }
        }
        return $copy;
    }
}
