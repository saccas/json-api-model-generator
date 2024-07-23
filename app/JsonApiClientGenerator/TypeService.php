<?php

namespace Saccas\JsonApiClientGenerator;

use Nette\PhpGenerator\Type;

class TypeService
{
    const string DATE_TIME = '\DateTime';

    public function getPhpTypeForApiType(string $apiType, ?string $format): string
    {
        return match ($apiType) {
            'integer' => \Nette\PhpGenerator\Type::Int,
            'number' => \Nette\PhpGenerator\Type::Int,
            'boolean' => \Nette\PhpGenerator\Type::Bool,
            'string' => match ($format) {
                'date' => self::DATE_TIME,
                'date-time' => self::DATE_TIME,
                default => Type::String,
            },
            default => $apiType,
        };
    }
}
