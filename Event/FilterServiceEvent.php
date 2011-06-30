<?php

namespace Tecbot\AMFBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Tecbot\AMFBundle\Amf\BodyRequest;

/**
 * Allows to filter a Amf service callable
 *
 * You can call getService() to retrieve the current Amf service. With
 * setService() you can set a new Amf service that is used in for processing
 * a request.
 *
 * Amf services should be callables.
 *
 * @author Thomas Adam <thomas.adam@tecbot.de>
 */
class FilterServiceEvent extends Event
{
    /**
     * The current Amf service
     *
     * @var callable
     */
    private $service;
    /**
     * The current body request
     *
     * @var BodyRequest
     */
    private $bodyRequest;

    /**
     * Constructor.
     *
     * @param $service
     * @param BodyRequest $bodyRequest
     */
    public function __construct($service, BodyRequest $bodyRequest)
    {
        $this->setService($service);
        $this->bodyRequest = $bodyRequest;
    }

    /**
     * Returns the current Amf service
     *
     * @return callable
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Sets a new Amf service
     *
     * @param callable $controller
     */
    public function setService($service)
    {
        // controller must be a callable
        if (!is_callable($service)) {
            throw new \LogicException(sprintf('The Amf service must be a callable (%s given).', $this->varToString($service)));
        }

        $this->service = $service;
    }

    /**
     * Returns the current body request object
     *
     * @return BodyRequest
     */
    public function getBodyRequest()
    {
        return $this->bodyRequest;
    }

    private function varToString($var)
    {
        if (is_object($var)) {
            return sprintf('Object(%s)', get_class($var));
        }

        if (is_array($var)) {
            $a = array();
            foreach ($var as $k => $v) {
                $a[] = sprintf('%s => %s', $k, $this->varToString($v));
            }

            return sprintf("Array(%s)", implode(', ', $a));
        }

        if (is_resource($var)) {
            return sprintf('Resource(%s)', get_resource_type($var));
        }

        if (null === $var) {
            return 'null';
        }

        if (false === $var) {
            return 'false';
        }

        if (true === $var) {
            return 'true';
        }

        return (string)$var;
    }
}
