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

        foreach (array('amf', 'controller', 'metadata') as $name) {
            $loader->load(sprintf('%s.xml', $name));
        }

        // metadata
        if ('none' === $config['metadata']['cache']) {
            $container->removeAlias('tecbot_amf.metadata.cache');
        } else if ('file' === $config['metadata']['cache']) {
            $container
                ->getDefinition('tecbot_amf.metadata.cache.file_cache')
                ->replaceArgument(0, $config['metadata']['file_cache']['dir'])
            ;

            $dir = $container->getParameterBag()->resolveValue($config['metadata']['file_cache']['dir']);
            if (!file_exists($dir)) {
                if (!$rs = @mkdir($dir, 0777, true)) {
                    throw new RuntimeException(sprintf('Could not create cache directory "%s".', $dir));
                }
            }
        } else {
            $container->setAlias('tecbot_amf.metadata.cache', new Alias($config['metadata']['cache'], false));
        }
        $container
            ->getDefinition('tecbot_amf.metadata_factory')
            ->replaceArgument(2, $config['metadata']['debug'])
        ;

        // directories
        $directories = array();
        if ($config['metadata']['auto_detection']) {
            foreach ($container->getParameter('kernel.bundles') as $name => $class) {
                $ref = new \ReflectionClass($class);

                $directories[$ref->getNamespaceName()] = dirname($ref->getFileName()).'/Resources/config/amf';
            }
        }
        foreach ($config['metadata']['directories'] as $directory) {
            $directories[rtrim($directory['namespace_prefix'], '\\')] = rtrim($directory['path'], '\\/');
        }
        $container
            ->getDefinition('tecbot_amf.metadata.file_locator')
            ->replaceArgument(0, $directories)
        ;

        $this->addClassesToCompile(array(
                'Tecbot\\AMFBundle\\Amf\\AmfEvents',
                'Tecbot\\AMFBundle\\Amf\\BodyRequest',
                'Tecbot\\AMFBundle\\Amf\\Server',

                'Tecbot\\AMFBundle\\Controller\\GatewayController',

                'Tecbot\\AMFBundle\\Amf\\Service\\ServiceNameParser',
                'Tecbot\\AMFBundle\\Amf\\Service\\ServiceResolver',
                'Tecbot\\AMFBundle\\Amf\\Service\\ServiceResolverInterface',

                'Tecbot\\AMFBundle\\Event\\FilterBodyResponseEvent',
                'Tecbot\\AMFBundle\\Event\\FilterServiceEvent',
                'Tecbot\\AMFBundle\\Event\\GetBodyResponseEvent',

                'Tecbot\\AMFBundle\\HttpFoundation\\Response',
            ));
    }
}