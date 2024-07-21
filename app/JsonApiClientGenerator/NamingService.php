<?php

namespace SacCas\JsonApiClientGenerator;

use Symfony\Component\String\UnicodeString;

class NamingService
{
    protected array $pluralMapping = [
        // 'person' => 'people'
    ];

    public function getClassForSchema(string $schema): string
    {
        return ucfirst((new UnicodeString($schema))->camel());
    }

    public function getPropertyName(string $apiPropertyName): string
    {
        return (new UnicodeString($apiPropertyName))->camel();
    }

    public function singular(string $name): string
    {
        return array_flip($this->pluralMapping)[$name] ?? substr($name, -1);
    }

    public function plural(string $name): string
    {
        return $this->pluralMapping[$name] ?? ($name . 's');
    }
}
