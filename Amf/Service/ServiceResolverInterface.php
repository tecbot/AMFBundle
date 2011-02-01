<?php

namespace Tecbot\AMFBundle\Amf\Service;

use Tecbot\AMFBundle\Amf\Request;

/**
 * ServiceResolverInterface.
 * 
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
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
     * @param  Request       $request A Request instance
     *
     * @return mixed|Boolean A PHP callable representing the AMF Service,
     *                       or false if this resolver is not able to determine the AMF service
     *
     * @throws \InvalidArgumentException|\LogicException If the AMF service can't be found
     */
    function getService(Request $request);

    /**
     * Returns the arguments to pass to the AMF service.
     *
     * @param Request $request   A Request instance
     * @param array   $service
     *
     * @throws \RuntimeException When value for argument given is not provided
     */
    function getArguments(Request $request, array $service);
}