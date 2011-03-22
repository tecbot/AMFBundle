<?php

namespace Tecbot\AMFBundle\Amf\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Allows to filter a AMF service callable
 *
 * You can call getService() to retrieve the current AMF service. With
 * setService() you can set a new AMF service that is used in for processing
 * a request.
 *
 * AMF services should be callables.
 *
 * @author Thomas Adam <thomas.adam@tecbot.de>
 */
class FilterServiceEvent extends KernelEvent
{
    /**
     * The current AMF service
     * @var callable
     */
    private $service;

    public function __construct(HttpKernelInterface $kernel, $service, Request $request, $requestType)
    {
        parent::__construct($kernel, $request, $requestType);

        $this->setService($service);
    }

    /**
     * Returns the current AMF service
     *
     * @return callable
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Sets a new AMF service
     *
     * @param callable $controller
     */
    public function setService($service)
    {
        // controller must be a callable
        if (!is_callable($service)) {
            throw new \LogicException(sprintf('The AMF service must be a callable (%s given).', $this->varToString($service)));
        }

        $this->service = $service;
    }

    private function varToString($var)
    {
        if (is_object($var)) {
            return sprintf('[object](%s)', get_class($var));
        }

        if (is_array($var)) {
            $a = array();
            foreach ($var as $k => $v) {
                $a[] = sprintf('%s => %s', $k, $this->varToString($v));
            }

            return sprintf("[array](%s)", implode(', ', $a));
        }

        if (is_resource($var)) {
            return '[resource]';
        }

        return str_replace("\n", '', var_export((string)$var, true));
    }
}
