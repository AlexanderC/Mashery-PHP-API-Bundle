<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/21/14
 * Time: 12:14
 */

namespace AlexanderC\Api\MasheryBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Gets the configuration tree builder for the extension.
     *
     * @return Tree The configuration tree builder
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $root = $tb->root('mashery_api');

        $root
            ->children()
                ->scalarNode('version')->defaultValue('version2')->end()
                ->scalarNode('transport')->defaultValue('curl')->end()
                ->scalarNode('client')->defaultValue(null)->end()
                ->scalarNode('application')->isRequired()->end()
                ->scalarNode('api_key')->isRequired()->end()
                ->scalarNode('secret')->isRequired()->end()
            ->end()
        ;

        return $tb;
    }
} 