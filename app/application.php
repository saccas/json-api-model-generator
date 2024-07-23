<?php
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

$container = new ContainerBuilder();
$loader = new YamlFileLoader($container, new FileLocator(__DIR__));
$loader->load('config/services.yaml');
$container->compile();

$application = new Application();
$command = $container->get('Saccas\JsonApiClientGenerator\Command\GenerateCommand');
$application->add($command);
$application->run();
