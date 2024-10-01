<?php

namespace Saccas\JsonApiClientGenerator\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Saccas\JsonApiClientGenerator\NamingService;

class RepositoryGenerator
{
    public function __construct(
        protected NamingService $namingService,
    ) {
    }

    public function generateRepositoryForSchema(
        string $classFolder,
        string $namespaceName,
        string $modelSchemaName,
        array $modelSchema,
        array $modelSchemaConfiguration,
    ): ClassType
    {
        $modelNamespace = new PhpNamespace($namespaceName . '\\Repository');

        $modelClassName = $this->namingService->getClassForSchema($modelSchemaName);
        $modelClassNameQualified = "\\{$namespaceName}\\Model\\{$modelClassName}";

        $className = $modelClassName . 'Repository';
        $class = $modelNamespace->addClass($className);
        $class->setExtends('\Saccas\JsonApiModel\JsonApiRepository');
        $class->addComment("@extends \Saccas\JsonApiModel\JsonApiRepository<{$modelClassNameQualified}>");

        $endpoint = $modelSchemaConfiguration[$modelSchemaName]['endpoint'] ?? "/api/{$modelSchemaName}";
        $endpointProperty = $class->addProperty('endpoint', $endpoint);
        $endpointProperty->setVisibility('protected');
        $endpointProperty->setType('string');

        $modelClassProperty = $class->addProperty('modelClass', "$modelClassNameQualified");
        $modelClassProperty->setVisibility('protected');
        $modelClassProperty->setType('string');

        $file = new PhpFile();
        $file->addNamespace($modelNamespace);

        $repositoryDirectory = $classFolder . '/Repository';
        if (!is_dir($repositoryDirectory)) {
            mkdir($repositoryDirectory, recursive: true);
        }
        $filename = $className . '.php';
        $path = $repositoryDirectory . '/' . $filename;
        file_put_contents($path, $file);

        return $class;
    }
}