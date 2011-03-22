<?php

namespace Tecbot\AMFBundle\Amf\Service;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * ServiceNameParser converts service from the short notation a:b
 * (FooBarBundle:Foo) to a fully-qualified class string
 * (Foo\BarBundle\AMF\FooService);
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
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
     * @param string $service A short notation AMF service (a:b)
     *
     * @param string A service (class)
     */
    public function parse($service)
    {
        if (2 != count($parts = explode(':', $service))) {
            throw new \InvalidArgumentException(sprintf('The "%s" AMF service is not a valid a:b AMF service string.', $service));
        }

        list($bundle, $service) = $parts;
        $bundle = strtr($bundle, array('/' => '\\'));
        $class = null;
        $logs = array();

        foreach ($this->kernel->getBundle($bundle, false) as $b) {
            $try = $b->getNamespace() . '\\AMF\\' . $service . 'Service';
            if (!class_exists($try)) {
                if (null !== $this->logger) {
                    $logs[] = sprintf('Failed finding AMF service "%s:%s" from namespace "%s" (%s)', $bundle, $service, $b->getNamespace(), $try);
                }
            } else {
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