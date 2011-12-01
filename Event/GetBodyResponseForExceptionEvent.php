<?php

namespace Tecbot\AMFBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Zend\Amf\Value\MessageBody;

/**
 * Allows to create a body response for a body request exception
 *
 * Call setBodyResponse() to set the body response that will be returned for the
 * current body request exception. The propagation of this event is stopped as soon as a
 * response is set.
 *
 * @author Thomas Adam <thomas.adam@tecbot.de>
 */
class GetBodyResponseForExceptionEvent extends Event
{
    /**
     * The current body
     *
     * @var MessageBody
     */
    private $body;
    /**
     * The current exception
     *
     * @var \Exception
     */
    private $exception;
    /**
     * The body response object
     *
     * @var mixed
     */
    private $bodyResponse;

    /**
     * Constructor.
     *
     * @param MessageBody $body
     */
    public function __construct(MessageBody $body, \Exception $exception)
    {
        $this->body = $body;
        $this->exception = $exception;
    }

    /**
     * Returns the current body object
     *
     * @return MessageBody
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns the current exception
     *
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
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