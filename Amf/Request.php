<?php

namespace Tecbot\AMFBundle\Amf;

use Symfony\Component\HttpFoundation\Request as BaseRequest;
use Zend\Amf\Request\StreamRequest;

/**
 * Request.
 *
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
class Request extends BaseRequest
{
    protected $streamRequest;
    protected $requestBody;

    /**
     * @return StreamRequest
     */
    public function getStreamRequest()
    {
        if (null === $this->streamRequest) {
            $this->streamRequest = new StreamRequest();
            $this->streamRequest->initialize($this->getContent());
        }

        return $this->streamRequest;
    }

    /**
     * @return RequestBody
     */
    public function getRequestBody()
    {
        return $this->requestBody;
    }

    /**
     * @param RequestBody $requestBody
     */
    public function setRequestBody(RequestBody $requestBody)
    {
        $this->requestBody = $requestBody;
    }
}