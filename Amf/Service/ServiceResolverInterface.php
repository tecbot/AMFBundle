<?php

namespace Tecbot\AMFBundle\Amf\Service;

use Tecbot\AMFBundle\Amf\BodyRequest;

/**
 * ServiceResolverInterface.
 *
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
interface ServiceResolverInterface
{
    /**
     * Returns the Amf service instance associated with a service name.
     *
     * As several resolvers can exist for a single application, a resolver must
     * return false when it is not able to determine the Amf service.
     *
     * The resolver must only throw an exception when it should be able to load
     * Amf service but cannot because of some errors made by the developer.
     *
     * @param  BodyRequest   $bodyRequest A BodyRequest instance
     *
     * @return mixed|Boolean A PHP callable representing the Amf service,
     *                       or false if this resolver is not able to determine the Amf service
     *
     * @throws \InvalidArgumentException|\LogicException If the Amf service can't be found
     */
    function getService(BodyRequest $bodyRequest);

    /**
     * Returns the arguments to pass to the Amf service.
     *
     * @param BodyRequest $bodyRequest A BodyRequest instance
     * @param array       $service
     *
     * @throws \RuntimeException When value for argument given is not provided
     */
    function getArguments(BodyRequest $bodyRequest, array $service);
}