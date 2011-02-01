<?php

namespace Tecbot\AMFBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * AMFExtension.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
class AMFExtension extends Extension
{
    protected $resources = array(
        'amf' => 'amf.xml',
        'security' => 'security.xml',
        'security_listeners' => 'security_listeners.xml',
    );
    protected $requestMatchers = array();
    protected $contextListeners = array();

    /**
     * Loads the AMF configuration.
     *
     * Usage example:
     *     security.config:
     *          providers:
     *              foo_provider:
     *                  id: foo.bar.provider
     * 
     *     amf.config:
     *          services:
     *              FooService: FooBarBundle:Foo
     *          mapping:
     *              FooClassVO : Foo\BarBundle\VO\FooClassVO
     *          security:
     *              provider: foo_provider
     *              firewalls:
     *                  public:
     *                      service: FooService
     *                      method: bar
     *                      provider: foo_provider
     *              access_control:
     *                  - { service: FooService, method: bar, role: [IS_AUTHENTICATED_FULLY] }
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

        $loader = new XmlFileLoader($container, __DIR__ . '/../Resources/config');
        $loader->load($this->resources['amf']);

        $container->setParameter('amf.services', Extension::normalizeConfig($config, 'service'));
        $container->setParameter('amf.mapping', Extension::normalizeConfig($config, 'mapping', 'mapping'));

        if (isset($config['security'])) {
            $loader->load($this->resources['security']);
            $loader->load($this->resources['security_listeners']);

            $this->createFirewalls($config['security'], $container);
            $this->createAuthorization($config['security'], $container);
        }
    }

    protected function createAuthorization($config, ContainerBuilder $container)
    {
        $rules = array();
        if (isset($config['access_control'])) {
            $rules = $config['access_control'];
        }

        if (isset($rules['rule']) && is_array($rules['rule'])) {
            $rules = $rules['rule'];
        }

        foreach ($rules as $i => $access) {
            $roles = isset($access['role']) ? (is_array($access['role']) ? $access['role'] : preg_split('/\s*,\s*/', $access['role'])) : array();
            $channel = null;
            if (isset($access['requires_channel'])) {
                $channel = $access['requires_channel'];
            }

            // matcher
            $services = $methods = $host = $ip = null;
            if (0 < count($tServices = $this->normalizeConfig($access, 'service'))) {
                $services = $tServices;
            }
            if (0 < count($tMethods = $this->normalizeConfig($access, 'method'))) {
                $methods = $tMethods;
            }
            if (isset($access['host'])) {
                $host = $access['host'];
            }
            if (isset($access['ip'])) {
                $ip = $access['ip'];
            }

            $matcher = $this->createRequestMatcher($container, $services, $methods, $host, $ip);

            $container->getDefinition('amf.security.access_map')->addMethodCall('add', array($matcher, $roles, $channel));
        }
    }

    protected function createFirewalls($config, ContainerBuilder $container)
    {
        if (!$firewalls = $this->normalizeConfig($config, 'firewall')) {
            return;
        }

        // make the ContextListener aware of the configured user providers
        $definition = $container->getDefinition('amf.security.context_listener');
        $arguments = $definition->getArguments();
        $userProviders = array();
        $providerIds = $this->normalizeConfig($config, 'provider');
        foreach ($providerIds as $providerId) {
            $userProviders[] = new Reference($this->getUserProviderId($providerId));
        }
        $arguments[1] = $userProviders;
        $definition->setArguments($arguments);

        // load firewall map
        $mapDef = $container->getDefinition('amf.security.firewall.map');
        $names = $map = array();
        foreach ($firewalls as $name => $firewall) {
            if (isset($firewall['name'])) {
                $name = $firewall['name'];
            }
            if (in_array($name, $names)) {
                throw new \RuntimeException(sprintf('The firewall name must be unique. Duplicate found: "%s"', $name));
            }
            $names[] = $name;

            list($matcher, $listeners, $exceptionListener) = $this->createFirewall($container, $name, $firewall, $providerIds);

            $contextId = 'amf.security.firewall.map.context.' . $name;
            $context = $container->setDefinition($contextId, new DefinitionDecorator('security.firewall.context'));
            $context
                    ->setArgument(0, $listeners)
                    ->setArgument(1, $exceptionListener)
            ;
            $map[$contextId] = $matcher;
        }
        $mapDef->setArgument(1, $map);
    }

    protected function createFirewall(ContainerBuilder $container, $id, $firewall, $providerIds)
    {

        $services = $methods = null;
        if (0 < count($tServices = $this->normalizeConfig($firewall, 'service'))) {
            $services = $tServices;
        }
        if (0 < count($tMethods = $this->normalizeConfig($firewall, 'method'))) {
            $methods = $tMethods;
        }

        $matcher = $this->createRequestMatcher($container, $services, $methods);

        // Security disabled?
        if (isset($firewall['security']) && !$firewall['security']) {
            return array($matcher, array(), null);
        }

        // Context serializer listener
        if (!isset($firewall['stateless']) || !$firewall['stateless']) {
            $contextKey = $id;
            if (isset($firewall['context'])) {
                $contextKey = $firewall['context'];
            }

            $listeners[] = new Reference($this->createContextListener($container, $contextKey));
        }

        // Access listener
        $listeners[] = new Reference('amf.security.access_listener');

        // Exception listener
        $exceptionListener = new Reference($this->createExceptionListener($container, $firewall, $id));

        return array($matcher, $listeners, $exceptionListener);
    }

    protected function createContextListener($container, $contextKey)
    {
        if (isset($this->contextListeners[$contextKey])) {
            return $this->contextListeners[$contextKey];
        }

        $listenerId = 'amf.security.context_listener.' . count($this->contextListeners);
        $listener = $container->setDefinition($listenerId, new DefinitionDecorator('amf.security.context_listener'));
        $listener->setArgument(2, $contextKey);

        return $this->contextListeners[$contextKey] = $listenerId;
    }

    protected function createExceptionListener($container, $config, $id)
    {
        $exceptionListenerId = 'amf.security.exception_listener.' . $id;
        $listener = $container->setDefinition($exceptionListenerId, new DefinitionDecorator('security.exception_listener'));

        // access denied handler setup
        if (isset($config['access_denied_handler'])) {
            $listener->setArgument(4, new Reference($config['access_denied_handler']));
        }

        return $exceptionListenerId;
    }

    protected function createRequestMatcher($container, $services = null, $methods = null, $host = null, $ip = null)
    {
        $serialized = serialize(array($services, $methods, $host, $ip));
        $id = 'amf.security.request_matcher.' . md5($serialized) . sha1($serialized);

        if (isset($this->requestMatchers[$id])) {
            return $this->requestMatchers[$id];
        }

        // only add arguments that are necessary
        $arguments = array($services, $methods, $host, $ip);
        while (count($arguments) > 0 && !end($arguments)) {
            array_pop($arguments);
        }

        $container
                ->register($id, '%amf.security.matcher.class%')
                ->setPublic(false)
                ->setArguments($arguments)
        ;

        return $this->requestMatchers[$id] = new Reference($id);
    }

    protected function getUserProviderId($name)
    {
        return 'security.authentication.provider.' . $name;
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