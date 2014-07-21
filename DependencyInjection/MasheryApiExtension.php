<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/21/14
 * Time: 12:22
 */

namespace AlexanderC\Api\MasheryBundle\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;
use AlexanderC\Api\Mashery\Mashery as MasheryApi;

class MasheryApiExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // add the service...
        $container->set(
            'mashery.api',
            MasheryApi::create(
                $config['api_key'], $config['secret'], $config['application'],
                $config['transport'], $config['version']
            )
        );
    }
} 