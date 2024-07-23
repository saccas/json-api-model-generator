<?php

declare(strict_types=1);

namespace Saccas\JsonApiClientGenerator\Command;

use Saccas\JsonApiClientGenerator\Generator\ApiGenerator;
use Saccas\JsonApiClientGenerator\Generator\ModelGenerator;
use Saccas\JsonApiClientGenerator\Generator\RepositoryGenerator;
use Saccas\JsonApiClientGenerator\NamingService;
use Saccas\JsonApiClientGenerator\RelationshipTypeService;
use Saccas\JsonApiClientGenerator\TypeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'app:generate', description: 'Hello PhpStorm')]
class GenerateCommand extends Command
{
    public function __construct(
        protected Filesystem $filesystem,
        protected NamingService $namingService,
        protected TypeService $typeService,
        protected ModelGenerator $modelGenerator,
        protected RepositoryGenerator $repositoryGenerator,
        protected ApiGenerator $apiGenerator,
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
        $this->filesystem->mirror('/in', '/out/generator_in');

        $sourceDirectory = '/out/src';
        $this->filesystem->mkdir($sourceDirectory);

        $specification = Yaml::parseFile('/in/openapi.yaml');
        $configuration = YAML::parseFile('/in/generator_configuration.yaml');

        $namespaceName = $input->getOption('namespace');
        $this->relationshipTypeService = new RelationShipTypeService($configuration['model_schemas']);

        $nonModelSchemaRegexp = '/(types)|(^jsonapi_)|(_(relationships|resource|single|collection|request|readable_attribute|readable_attributes_list|sortable_attributes_list|related|extra_attribute)$)/';

        $schemas = $specification['components']['schemas'];
        $modelSchemas = array_filter($schemas, fn(string $key) => !preg_match($nonModelSchemaRegexp, $key), ARRAY_FILTER_USE_KEY);

        $this->apiGenerator->generateJsonApiManager(
            $sourceDirectory,
            $namespaceName,
            $modelSchemas,
        );
        foreach ($modelSchemas as $modelSchemaName => $modelSchema) {
            $this->modelGenerator->generateModelForSchema(
                $sourceDirectory,
                $namespaceName,
                $modelSchemaName,
                $modelSchema,
                $schemas[$modelSchemaName . '_relationships'] ?? null,
                $this->relationshipTypeService,
            );
            $this->repositoryGenerator->generateRepositoryForSchema(
                $sourceDirectory,
                $namespaceName,
                $modelSchemaName,
                $modelSchema,
                $configuration['model_schemas'],
            );
        }

        return Command::SUCCESS;
    }
}
