<?php

namespace Tecbot\AMFBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * AMFExtension.
 *
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
class TecbotAMFExtension extends Extension
{
    /**
     * Loads the AMF configuration.
     *
     * @param array            $configs   An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration($container->getParameter('kernel.debug'));
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        foreach (array('amf', 'controller') as $name) {
            $loader->load(sprintf('%s.xml', $name));
        }

        // services & value objects
        $container->setParameter('tecbot_amf.services', $config['services']);
        $container->setParameter('tecbot_amf.mappings', $config['mappings']);

        // serialization
        if (true === $config['use_serialization']) {
            foreach (array('metadata', 'serializer') as $name) {
                $loader->load(sprintf('%s.xml', $name));
            }
        }

        // logging
        if(true === $config['logging']) {
            $loader->load('logger.xml');
        }

        $this->addClassesToCompile(array(
                'Tecbot\\AMFBundle\\Amf\\Service\\ServiceNameParser',
                'Tecbot\\AMFBundle\\Amf\\Service\\ServiceResolver',
                'Tecbot\\AMFBundle\\Amf\\Service\\ServiceResolverInterface',
                'Tecbot\\AMFBundle\\Amf\\AmfEvents',
                'Tecbot\\AMFBundle\\Amf\\BodyRequest',
                'Tecbot\\AMFBundle\\Amf\\Server',

                'Tecbot\\AMFBundle\\Controller\\GatewayController',

                'Tecbot\\AMFBundle\\Event\\FilterBodyResponseEvent',
                'Tecbot\\AMFBundle\\Event\\FilterServiceEvent',
                'Tecbot\\AMFBundle\\Event\\GetBodyResponseEvent',

                'Tecbot\\AMFBundle\\HttpFoundation\\Response',

                'Tecbot\\AMFBundle\\Logger\\Handler\\AmfHandler',
            ));
    }
}