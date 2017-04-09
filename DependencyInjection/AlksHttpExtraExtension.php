<?php

namespace Alks\HttpExtraBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class AlksHttpExtraExtension
 * @package Alks\HttpExtraBundle\DependencyInjection
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class AlksHttpExtraExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $configDefinition = $container->getDefinition('alks_http.configuration_resolver');
        $configDefinition->addMethodCall('load', [$config]);
    }
}
