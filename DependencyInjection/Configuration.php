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
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('tecbot_amf');

        /*$rootNode
            ->children()
                ->booleanNode('test')->end()
            ->end()
        ;*/

        $this->addServicesSection($rootNode);
        $this->addMappingsSection($rootNode);
        //$this->addSecuritySection($rootNode);

        return $tb;
    }

    private function addServicesSection(ArrayNodeDefinition $rootNode)
    {
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
        ;
    }

    private function addMappingsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
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
    }

    private function addSecuritySection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('security')
                    ->fixXmlConfig('firewall')
                    ->children()
                        ->arrayNode('firewalls')
                            ->isRequired()
                            ->requiresAtLeastOneElement()
                            ->disallowNewKeysInSubsequentConfigs()
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('provider')->isRequired()->cannotBeEmpty()->end()
                                    ->arrayNode('services')
                                        ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->fixXmlConfig('rule', 'access_control')
                    ->children()
                        ->arrayNode('access_control')
                            ->cannotBeOverwritten()
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('requires_channel')->defaultNull()->end()
                                    ->arrayNode('services')
                                        ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                    ->scalarNode('host')->defaultNull()->end()
                                    ->scalarNode('ip')->defaultNull()->end()
                                    ->arrayNode('methods')
                                        ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                                ->fixXmlConfig('role')
                                ->children()
                                    ->arrayNode('roles')
                                        ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}