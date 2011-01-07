<?php

namespace Bundle\Tecbot\AMFBundle\Service\Resolver;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * ServiceNameConverter converts service from the short notation a:b
 * (BlogBundle:Post) to a fully-qualified class string
 * (Bundle\BlogBundle\AMF\PostService); and the other
 * way around.
 */
class ServiceNameConverter
{
    protected $kernel;
    protected $logger;
    protected $namespaces;

    /**
     * Constructor.
     *
     * @param Kernel          $kernel A Kernel instance
     * @param LoggerInterface $logger A LoggerInterface instance
     */
    public function __construct(Kernel $kernel, LoggerInterface $logger = null)
    {
        $this->kernel = $kernel;
        $this->logger = $logger;
        $this->namespaces = array_keys($kernel->getBundleDirs());
    }

    /**
     * Converts a class string to the short notation a:b.
     *
     * @param string $class A AMF service
     *
     * @return string A short notation service (a:b)
     */
    public function toShortNotation($class)
    {
        if (!preg_match('/Service\\\(.*)Service$/', $class, $match)) {
            throw new \InvalidArgumentException(sprintf('The "%s" class does not look like a AMF service class (it does not end with Service)', $class));
        }
        $service = $match[1];

        $bundle = null;
        $namespace = substr($class, 0, strrpos($class, '\\'));
        foreach ($this->namespaces as $prefix) {
            if (0 === $pos = strpos($namespace, $prefix)) {
                // -4 to remove the \AMF suffix (4 characters)
                $bundle = substr($namespace, strlen($prefix) + 1, -4);
            }
        }

        if (null === $bundle) {
            throw new \InvalidArgumentException(sprintf('The "%s" class does not belong to a known bundle namespace.', $class));
        }

        return $bundle . ':' . $service;
    }

    /**
     * Converts a short notation a:b.
     *
     * @param string $service A short notation AMF service (a:b)
     *
     * @param string A service (class::method)
     */
    public function fromShortNotation($service)
    {
        if (2 != count($parts = explode(':', $service))) {
            throw new \InvalidArgumentException(sprintf('The "%s" AMF service is not a valid a:b AMF service string.', $service));
        }

        list($bundle, $service) = $parts;
        $bundle = strtr($bundle, array('/' => '\\'));
        $class = null;
        $logs = array();

        foreach ($this->namespaces as $namespace) {
            $try = $namespace . '\\' . $bundle . '\\AMF\\' . $service . 'Service';
            if (!class_exists($try)) {
                if (null !== $this->logger) {
                    $logs[] = sprintf('Failed finding AMF service "%s:%s" from namespace "%s" (%s)', $bundle, $service, $namespace, $try);
                }
            } else {
                if (!$this->kernel->isClassInActiveBundle($try)) {
                    throw new \LogicException(sprintf('To use the "%s" AMF service, you first need to enable the Bundle "%s" in your Kernel class.', $try, $namespace . '\\' . $bundle));
                }

                $class = $try;

                break;
            }
        }

        if (null === $class) {
            if (null !== $this->logger) {
                foreach ($logs as $log) {
                    $this->logger->info($log);
                }
            }

            throw new \InvalidArgumentException(sprintf('Unable to find AMF service "%s:%s".', $bundle, $service));
        }

        return $class;
    }
}