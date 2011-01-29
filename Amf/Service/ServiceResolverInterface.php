<?php

namespace Tecbot\AMFBundle\Amf\Service;

use Symfony\Component\HttpFoundation\Request;

interface ServiceResolverInterface
{
    /**
     * Returns the AMF Service instance associated with a service name.
     *
     * As several resolvers can exist for a single application, a resolver must
     * return false when it is not able to determine the AMF service.
     *
     * The resolver must only throw an exception when it should be able to load
     * AMF service but cannot because of some errors made by the developer.
     *
     * @param string $service A AMF Service
     * @param string $methode A AMF Service methode
     *
     * @return mixed|Boolean A PHP callable representing the AMF Service,
     *                       or false if this resolver is not able to determine the AMF service
     *
     * @throws \InvalidArgumentException|\LogicException If the AMF service can't be found
     */
    function getService($service, $methode);

    /**
     * Returns the arguments to pass to the AMF service.
     *
     * @param array $params
     * @param array $service
     *
     * @throws \RuntimeException When value for argument given is not provided
     */
    function getArguments(array $params, array $service);
}