<?php

namespace Tecbot\AMFBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Tecbot\AMFBundle\Amf\BodyRequest;

/**
 * Allows to filter a body response
 *
 * You can call getResponse() to retrieve the current response. With
 * setResponse() you can set a new response that will be returned to the
 * client.
 *
 * @author Thomas Adam <thomas.adam@tecbot.de>
 */
class FilterBodyResponseEvent extends Event
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
     * @param mixed       $bodyResponse
     */
    public function __construct(BodyRequest $bodyRequest, $bodyResponse)
    {
        $this->bodyRequest = $bodyRequest;
        $this->setBodyResponse($bodyResponse);
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
     * Returns the current body response object
     *
     * @return mixed
     */
    public function getBodyResponse()
    {
        return $this->bodyResponse;
    }

    /**
     *  Sets a new body response object
     *
     * @param mixed $bodyResponse
     */
    public function setBodyResponse($bodyResponse)
    {
        $this->bodyResponse = $bodyResponse;
    }
}
