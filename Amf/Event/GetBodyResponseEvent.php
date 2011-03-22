<?php

namespace Tecbot\AMFBundle\Amf\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tecbot\AMFBundle\Amf\RequestBody;

/**
 * Allows to create a body response for a request
 *
 * Call setBodyResponse() to set the body response that will be returned for the
 * current request. The propagation of this event is stopped as soon as a
 * response is set.
 *
 * @author Thomas Adam <thomas.adam@tecbot.de>
 */
class GetBodyResponseEvent extends KernelEvent
{
    /**
     * The body response object
     *
     * @var mixed
     */
    private $bodyResponse;
    private $requestBody;

    public function __construct(HttpKernelInterface $kernel, Request $request, $requestType, RequestBody $requestBody)
    {
        parent::__construct($kernel, $request, $requestType);

        $this->requestBody = $requestBody;
    }

    /**
     * Returns the request body object
     *
     * @return RequestBody
     */
    public function getRequestBody()
    {
        return $this->requestBody;
    }

    /**
     * Returns the body response object
     *
     * @return mixed
     */
    public function getBodyResponse()
    {
        return $this->bodyResponse;
    }

    /**
     * Sets a body response and stops event propagation
     *
     * @param mixed $response
     */
    public function setBodyResponse($bodyResponse)
    {
        $this->bodyResponse = $bodyResponse;

        $this->stopPropagation();
    }

    /**
     * Returns whether a body response was set
     *
     * @return Boolean Whether a body response was set
     */
    public function hasBodyResponse()
    {
        return null !== $this->bodyResponse;
    }
}