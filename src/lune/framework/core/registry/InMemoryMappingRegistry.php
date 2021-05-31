<?php

namespace lune\framework\core\registry;

use lune\framework\core\MappingInfo;
use lune\framework\util\PathUtils;

final class InMemoryMappingRegistry implements MappingRegistry
{
    private $registryDumpLocation;
    private $registry;

    public function __construct()
    {
        $this->registryDumpLocation = PathUtils::implode(APP_ROOT, "runtime", ".mappings");
        $this->registry = [];
    }

    public function register(string $pattern, MappingInfo $mappingInfo)
    {
        $this->registry[$pattern] = $mappingInfo;
    }

    public function contains(string $pattern): bool
    {
        return array_key_exists($pattern, $this->registry);
    }

    public function get(string $pattern): ?MappingInfo
    {
        return $this->registry[$pattern];
    }

    public function isEmpty(): bool
    {
        return count($this->registry) === 0;
    }

    public function load()
    {
        if (file_exists($this->registryDumpLocation)) {
            $this->registry = unserialize(file_get_contents($this->registryDumpLocation));
        }
    }

    public function dump()
    {
        $fp = fopen($this->registryDumpLocation, "w");
        if ($fp) {
            fwrite($fp, serialize($this->registry));
            fclose($fp);
        } else {
            echo "Failed to write mapping info to file. Please make sure Apache has the write permision.";
            exit();
        }  
    }

    public function forEach($consumer) {
        foreach ($this->registry as $pattern => $mappingInfo) {
            if ($consumer($pattern, $mappingInfo) === true) {
                break;
            }
        }
    }

}