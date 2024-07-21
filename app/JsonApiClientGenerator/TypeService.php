<?php

namespace SacCas\JsonApiClientGenerator;

use Symfony\Component\String\UnicodeString;

class TypeService
{
    public function getPhpTypeForApiType(string $apiType): string
    {
        return match ($apiType) {
            'integer' => 'int',
            'number' => 'int',
            'boolean' => 'bool',
            default => $apiType,
        };
    }
}
