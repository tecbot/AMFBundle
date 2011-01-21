<?php

namespace Tecbot\AMFBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AMFExtension extends Extension
{
    /**
     * Loads the AMF configuration.
     *
     * Usage example:
     *     amf.config:
     *          services:
     *              UserService: UserBundle:User
     *
     * @param array $config An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function configLoad($config, ContainerBuilder $container)
    {
        $this->loadDefaults($config, $container);
    }

    /**
     * Loads the default configuration.
     *
     * @param array $config An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDefaults(array $config, ContainerBuilder $container)
    {
        if (!$container->hasDefinition('amf')) {
            $loader = new XmlFileLoader($container, __DIR__ . '/../Resources/config');
            $loader->load('amf.xml');
        }
        
        

        if (isset($config['services']) && is_array($config['services'])) {
            $container->setParameter('amf.services', $config['services']);
        }
        
        if (isset($config['class_map']) && is_array($config['class_map'])) {
            $container->setParameter('amf.class_map', $config['class_map']);
        }
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return null;
    }

    public function getNamespace()
    {
        return 'http://www.tecbot.de/schema/dic/amf';
    }

    public function getAlias()
    {
        return 'amf';
    }
}