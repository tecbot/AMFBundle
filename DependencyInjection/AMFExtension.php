<?php

namespace Tecbot\AMFBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AMFExtension extends Extension
{
    protected $resources = array(
        'amf' => 'amf.xml',
    );

    /**
     * Loads the AMF configuration.
     *
     * Usage example:
     *     amf.config:
     *          services:
     *              UserService: UserBundle:User
     *
     * @param array $configs An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function configLoad($configs, ContainerBuilder $container)
    {
        $config = array_shift($configs);
        foreach ($configs as $tmp) {
            $config = array_replace_recursive($config, $tmp);
        }

        $this->loadDefaults($container);

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

    /**
     * Loads the default configuration.
     *
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDefaults(ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, __DIR__ . '/../Resources/config');
        foreach ($this->resources as $resource) {
            $loader->load($resource);
        }
    }
}