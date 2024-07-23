<?php

namespace Saccas\JsonApiClientGenerator\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Saccas\JsonApiClientGenerator\NamingService;
use Saccas\JsonApiClientGenerator\TypeService;

class ApiGenerator
{
    public function __construct(
        protected NamingService $namingService,
        protected TypeService $typeService,
    ) {
    }

    public function generateJsonApiManager(
        string $classFolder,
        string $namespaceName,
        array $modelSchemas,
    ): ClassType
    {
        $namespace = new PhpNamespace($namespaceName);
        $namespaceNameParticles = explode('\\', $namespaceName);
        $className = $namespaceNameParticles[count($namespaceNameParticles) -1];
        $class = $namespace->addClass($className);
        $class->setExtends('\Saccas\JsonApiModel\JsonApiManager');

        $lines = array_map(fn (string $apiSchema) => '\'' . $apiSchema . '\' => Repository\\' . $this->namingService->getClassForSchema($apiSchema) . 'Repository::class, ', array_keys($modelSchemas));
        $schemaRepositoryClassMapProperty = $class->addProperty('schemaRepositoryClassMap', new Literal('[' . implode($lines) . ']'));
        $schemaRepositoryClassMapProperty->setType('array');

        foreach($modelSchemas as $modelSchemaName => $modelSchema) {
            $modelName = $this->namingService->getClassForSchema($modelSchemaName);
            $repositoryName = $modelName . 'Repository';
            $getRepositoryMethod = $class->addMethod("get{$modelName}Repository");
            $getRepositoryMethod->setVisibility('public');
            $getRepositoryMethod->setReturnType($namespaceName . '\\Repository\\' . $repositoryName);
            $getRepositoryMethod->setBody("return \$this->getRepository('{$modelSchemaName}');");
        }

        $file = new PhpFile();
        $file->addNamespace($namespace);

        $filename = $className . '.php';
        $path = $classFolder . '/' . $filename;
        file_put_contents($path, $file);

        return $class;
    }
}