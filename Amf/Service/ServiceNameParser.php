<?php

namespace Tecbot\AMFBundle\Amf\Service;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * ServiceNameParser converts service from the short notation a:b
 * (FooBarBundle:Foo) to a fully-qualified class string
 * (Foo\BarBundle\Amf\FooService);
 *
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
class ServiceNameParser
{
    protected $kernel;
    protected $logger;

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
    }

    /**
     * Converts a short notation a:b to a class.
     *
     * @param string $service A short notation Amf service (a:b)
     *
     * @param string A service (class)
     */
    public function parse($service)
    {
        if (2 != count($parts = explode(':', $service))) {
            throw new \InvalidArgumentException(sprintf('The "%s" Amf service is not a valid a:b Amf service string.', $service));
        }

        list($bundle, $service) = $parts;
        $class = null;
        $logs = array();
        foreach ($this->kernel->getBundle($bundle, false) as $b) {
            $try = $b->getNamespace() . '\\Amf\\' . $service . 'Service';
            if (!class_exists($try)) {
                if (null !== $this->logger) {
                    $logs[] = sprintf('Unable to find Amf service "%s:%s" - class "%s" does not exist.', $bundle, $service, $try);
                }
            } else {
                $class = $try;

                break;
            }
        }

        if (null === $class) {
            $this->handleControllerNotFoundException($bundle, $service, $logs);
        }

        return $class;
    }

    private function handleControllerNotFoundException($bundle, $service, array $logs)
    {
        if (null !== $this->logger) {
            foreach ($logs as $log) {
                $this->logger->info($log);
            }
        }

        // just one log, return it as the exception
        if (1 == count($logs)) {
            throw new \InvalidArgumentException($logs[0]);
        }

        // many logs, use a message that mentions each searched bundle
        $names = array();
        foreach ($this->kernel->getBundle($bundle, false) as $b) {
            $names[] = $b->getName();
        }
        $msg = sprintf('Unable to find Amf service "%s:%s" in bundles %s.', $bundle, $service, implode(', ', $names));

        throw new \InvalidArgumentException($msg);
    }
}