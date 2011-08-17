<?php

namespace Tecbot\AMFBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * AmfBundle configuration structure.
 *
 * @author Thomas Adam <thomas.adam@tecbot.de>
 */
class Configuration implements ConfigurationInterface
{
    private $debug;

    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('tecbot_amf');

        $this->addMetaDataSection($rootNode);

        return $tb;
    }

    private function addServicesSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('metadata')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('directory', 'directories')
                    ->children()
                        ->scalarNode('cache')->defaultValue('file')->end()
                        ->booleanNode('debug')->defaultValue($this->debug)->end()
                        ->arrayNode('file_cache')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('dir')->defaultValue('%kernel.cache_dir%/amf')->end()
                            ->end()
                        ->end()
                        ->booleanNode('auto_detection')->defaultTrue()->end()
                        ->arrayNode('directories')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('path')->isRequired()->end()
                                    ->scalarNode('namespace_prefix')->defaultValue('')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}