<?php

namespace Tecbot\AMFBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 */
class Configuration
{
    /**
     * Generates the configuration tree.
     *
     * @return \Symfony\Component\DependencyInjection\Configuration\NodeInterface
     */
    public function getConfigTree()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tecbot_amf');

        $rootNode
            ->fixXmlConfig('service')
            ->children()
                ->arrayNode('services')
                    ->useAttributeAsKey('alias')
                    ->prototype('array')
                        ->performNoDeepMerging()
                        ->beforeNormalization()->ifString()->then(function($v) { return array('class' => $v); })->end()
                        ->children()
                            ->scalarNode('class')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->fixXmlConfig('mapping')
            ->children()
                ->arrayNode('mappings')
                    ->useAttributeAsKey('alias')
                    ->prototype('array')
                        ->performNoDeepMerging()
                        ->beforeNormalization()->ifString()->then(function($v) { return array('class' => $v); })->end()
                        ->children()
                            ->scalarNode('class')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
        ;

        return $treeBuilder->buildTree();
    }
}