<?php

namespace Tecbot\AMFBundle\Amf\Service;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class ServiceResolver implements ServiceResolverInterface
{
    protected $container;
    protected $parser;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     * @param ServiceNameParser  $parser    A ServiceNameparser instance
     * @param LoggerInterface    $logger    A LoggerInterface instance
     */
    public function __construct(ContainerInterface $container, ServiceNameParser $parser, LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->parser = $parser;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getService($service, $methode)
    {
        $serviceClass = $this->createService($service);
        $methode = $methode . 'Action'; // add methode suffix

        if (!method_exists($serviceClass, $methode)) {
            throw new \InvalidArgumentException(sprintf('Method "%s::%s" does not exist.', get_class($serviceClass), $methode));
        }

        if (null !== $this->logger) {
            $this->logger->info(sprintf('Using AMF service "%s::%s"', get_class($serviceClass), $methode));
        }

        return array($serviceClass, $methode);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(array $params, array $service)
    {
        list($serviceClass, $method) = $service;
        $m = new \ReflectionMethod($serviceClass, $method);
        $parameters = $m->getParameters();
        
        if (0 < count($parameters)) {
            $params = array_merge($params, $parameters);
        }
        
        return $params;
    }

    /**
     * Returns a callable for the given amf service.
     *
     * @param  string $service A AMF Service string
     *
     * @return mixed  A PHP callable
     */
    protected function createService($service)
    {
        $count = substr_count($service, ':');
        if (1 == $count) {
            // AMF service in the a:b notation then
            $service = $this->parser->parse($service);
        } else if (0 == $count) {
            // AMF service in the service notation
            return $this->container->get($service);
        } else {
            throw new \LogicException(sprintf('Unable to parse the AMF service name "%s".', $service));
        }

        if (!class_exists($service)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $service));
        }

        $serviceClass = new $service();
        if ($serviceClass instanceof ContainerAwareInterface) {
            $serviceClass->setContainer($this->container);
        }

        return $serviceClass;
    }
}