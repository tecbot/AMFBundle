<?php

namespace Tecbot\AMFBundle\Amf;

use Symfony\Component\HttpFoundation\Request as BaseRequest;
use Zend\Amf\Request\StreamRequest;

/**
 * Description of Request
 *
 * @author Thomas
 */
class Request extends BaseRequest
{
    /**
     * @var Zend\Amf\Request\StreamRequest
     */
    protected $streamRequest;

    /**
     * @return Zend\Amf\Request\StreamRequest
     */
    public function getStreamRequest()
    {
        if (null === $this->streamRequest) {
            $this->streamRequest = new StreamRequest();
            $this->streamRequest->initialize($this->getContent());
        }

        return $this->streamRequest;
    }
}