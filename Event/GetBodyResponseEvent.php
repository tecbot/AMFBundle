<?php

namespace Tecbot\AMFBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Tecbot\AMFBundle\Amf\BodyRequest;

/**
 * Allows to create a body response for a body request
 *
 * Call setBodyResponse() to set the body response that will be returned for the
 * current body request. The propagation of this event is stopped as soon as a
 * response is set.
 *
 * @author Thomas Adam <thomas.adam@tecbot.de>
 */
class GetBodyResponseEvent extends Event
{
    /**
     * The current body request
     *
     * @var BodyRequest
     */
    private $bodyRequest;
    /**
     * The body response object
     *
     * @var mixed
     */
    private $bodyResponse;

    /**
     * Constructor.
     *
     * @param BodyRequest $bodyRequest
     */
    public function __construct(BodyRequest $bodyRequest)
    {
        $this->bodyRequest = $bodyRequest;
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
     * @param mixed $bodyResponse
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