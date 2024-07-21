<?php

declare(strict_types=1);

namespace SacCas\JsonApiClientGenerator\Command;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use SacCas\JsonApiClientGenerator\NamingService;
use SacCas\JsonApiClientGenerator\RelationshipTypeService;
use SacCas\JsonApiClientGenerator\TypeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'app:generate', description: 'Hello PhpStorm')]
class GenerateCommand extends Command
{
    public function __construct(
        protected NamingService $namingService,
        protected TypeService $typeService,
    ) {
        parent::__construct();
    }

    protected RelationshipTypeService $relationshipTypeService;

    #[\Override] protected function configure()
    {
        parent::configure();
        $this->addOption('namespace', null, InputOption::VALUE_REQUIRED);
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceDirectory = '/out/src';
        $modelDirectory = $sourceDirectory . '/Model';
        if (!is_dir($sourceDirectory)) {
            mkdir($sourceDirectory);
        }
        if (!is_dir($modelDirectory)) {
            mkdir($modelDirectory);
        }

        $specification = Yaml::parseFile('/in/openapi.yaml');
        $namespaceName = $input->getOption('namespace');

        $relationshipTypes = YAML::parseFile('/in/relationship_types.yaml');
        $this->relationshipTypeService = new RelationShipTypeService($relationshipTypes);

        $nonModelSchemaRegexp = '/(types)|(^jsonapi_)|(_(relationships|resource|single|collection|request|readable_attribute|readable_attributes_list|sortable_attributes_list|related|extra_attribute)$)/';

        $schemas = $specification['components']['schemas'];
        $modelSchemas = array_filter($schemas, fn(string $key) => !preg_match($nonModelSchemaRegexp, $key), ARRAY_FILTER_USE_KEY);

        foreach ($modelSchemas as $modelSchemaName => $modelSchema) {
            $file = $this->generateFileForModelSchema(
                modelSchemaName: $modelSchemaName,
                modelSchema: $modelSchema,
                relationshipsSchema: $schemas[$modelSchemaName . '_relationships'] ?? null,
                namespaceName: $namespaceName
            );
            $filename = $this->namingService->getClassForSchema($modelSchemaName) . '.php';
            $path = $modelDirectory . '/' . $filename;
            file_put_contents($path, $file);
        }

        foreach ($modelSchemas as $modelSchemaName => $_) {
            $className = $this->namingService->getClassForSchema($modelSchemaName);
            $repository = new ClassType($className);
        }

        return Command::SUCCESS;
    }

    protected function generateFileForModelSchema(
        string $modelSchemaName,
        array $modelSchema,
        ?array $relationshipsSchema,
        string $namespaceName
    ): PhpFile {
        $modelNamespace = new PhpNamespace($namespaceName . '\\Model');

        $className = $this->namingService->getClassForSchema($modelSchemaName);
        $class = $modelNamespace->addClass($className);
        $class->setExtends('\Swis\JsonApi\Client\Item');

        $typeProperty = $class->addProperty('type', $modelSchemaName);
        $typeProperty->setProtected();

        foreach($modelSchema['properties'] as $propertyApiName => $propertyDescription) {
            $propertyName = $this->namingService->getPropertyName($propertyApiName);
            $apiType = $propertyDescription['type'];
            $phpType = $this->typeService->getPhpTypeForApiType($apiType);
            $prefix = $phpType === 'bool' ? 'is' : 'get';
            $postfix = ucfirst($propertyName);
            $methodName = $prefix . $postfix;
            $method = $class->addMethod($methodName);
            $method->setBody(sprintf('return $this->getAttribute(\'%s\');', $propertyApiName));
            $method->setReturnType($phpType);
        }

        if (isset($relationshipsSchema, $relationshipsSchema['properties'])) {
            foreach($relationshipsSchema['properties'] as $relationShipName => $relationShipSpec)
            {
                $pluralRelationship = str_ends_with($relationShipSpec['properties']['data']['$ref'], '_relationshipToMany');
                $apiType = $this->relationshipTypeService->getTypeForRelationship($modelSchemaName, $relationShipName, $relationShipSpec);
                $returnTypePhpSingle = '\\' . $modelNamespace->resolveName($this->namingService->getClassForSchema($apiType));

                $methodName = 'get' . ucfirst($this->namingService->getPropertyName($relationShipName));
                $method = $class->addMethod($methodName);
                if ($pluralRelationship) {
                    $method->setComment('@return \Illuminate\Support\Collection<int, ' . $returnTypePhpSingle . '>|null');
                    $method->setReturnType('?\Swis\JsonApi\Client\Collection');
                    $method->setBody(sprintf('return $this->getRelationValue(\'%s\');', $relationShipName));
                } else {
                    $method->setReturnType('?' . $returnTypePhpSingle);
                    $method->setBody(sprintf('return $this->getRelationValue(\'%s\');', $relationShipName));
                }
            }
        }

        $file = new PhpFile();
        $file->addNamespace($modelNamespace);
        return $file;
    }
}
