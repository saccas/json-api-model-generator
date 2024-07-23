<?php

namespace Saccas\JsonApiClientGenerator\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Saccas\JsonApiClientGenerator\NamingService;
use Saccas\JsonApiClientGenerator\RelationshipTypeService;
use Saccas\JsonApiClientGenerator\TypeService;

class ModelGenerator
{
    public function __construct(
        protected NamingService $namingService,
        protected TypeService $typeService,
    ) {
    }

    public function generateModelForSchema(
        string $classFolder,
        string $namespaceName,
        string $modelSchemaName,
        array $modelSchema,
        ?array $relationshipsSchema,
        RelationshipTypeService $relationshipTypeService,
    ): ClassType
    {
        $modelNamespace = new PhpNamespace($namespaceName . '\\Model');

        $className = $this->namingService->getClassForSchema($modelSchemaName);
        $class = $modelNamespace->addClass($className);
        $class->setExtends('\Saccas\JsonApiModel\JsonApiModel');

        $getIdMethod = $class->addMethod('getId');
        $getIdMethod->setBody('return $this->getAttribute(\'id\');');
        $getIdMethod->setReturnType('string');

        foreach($modelSchema['properties'] as $propertyApiName => $propertyDescription) {
            $propertyName = $this->namingService->getPropertyName($propertyApiName);
            $phpType = $this->typeService->getPhpTypeForApiType($propertyDescription['type'], $propertyDescription['format'] ?? null);
            $prefix = $phpType === 'bool' ? 'is' : 'get';
            $postfix = ucfirst($propertyName);
            $methodName = $prefix . $postfix;
            $method = $class->addMethod($methodName);

            $internalGetter = $phpType === TypeService::DATE_TIME ? 'getDateAttribute' : 'getAttribute';
            $method->setBody("return \$this->{$internalGetter}('{$propertyApiName}');");
            $method->setReturnType($phpType);
            $method->setReturnNullable();
        }

        if (isset($relationshipsSchema, $relationshipsSchema['properties'])) {
            foreach($relationshipsSchema['properties'] as $relationShipName => $relationShipSpec)
            {
                $pluralRelationship = str_ends_with($relationShipSpec['properties']['data']['$ref'], '_relationshipToMany');
                $apiSchema = $relationshipTypeService->getSchemaForRelationship($modelSchemaName, $relationShipName, $relationShipSpec);
                $returnTypePhpSingle = '\\' . $modelNamespace->resolveName($this->namingService->getClassForSchema($apiSchema));

                $methodName = 'get' . ucfirst($this->namingService->getPropertyName($relationShipName));
                $method = $class->addMethod($methodName);
                if ($pluralRelationship) {
                    $method->setComment('@return ?\Illuminate\Support\Collection<' . $returnTypePhpSingle . '>');
                    $method->setReturnType('?\Illuminate\Support\Collection');
                    $method->setBody("return \$this->getRelationMultiple('{$relationShipName}', {$returnTypePhpSingle}::class);");
                } else {
                    $method->setReturnType('?' . $returnTypePhpSingle);
                    $method->setBody("return \$this->getRelationSingle('{$relationShipName}', {$returnTypePhpSingle}::class);");
                }
            }
        }

        $modelDirectory = $classFolder . '/Model';
        if (!is_dir($modelDirectory)) {
            mkdir($modelDirectory, recursive: true);
        }

        $file = new PhpFile();
        $file->addNamespace($modelNamespace);

        $filename = $className . '.php';
        $path = $modelDirectory . '/' . $filename;
        file_put_contents($path, $file);

        return $class;
    }
}