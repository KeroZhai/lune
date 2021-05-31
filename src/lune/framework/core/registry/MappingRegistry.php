<?php

namespace lune\framework\core\registry;

use lune\framework\core\MappingInfo;

interface MappingRegistry
{
    public function register(string $pattern, MappingInfo $mappingInfo);

    public function contains(string $pattern): bool;

    public function get(string $pattern): ?MappingInfo;

    public function isEmpty(): bool;

}