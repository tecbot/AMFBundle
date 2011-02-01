<?php

namespace Tecbot\AMFBundle\Amf;

use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Zend\Amf\Response\StreamResponse;

/**
 * Response.
 *
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
class Response extends BaseResponse
{
    protected $streamResponse;

    /**
     * {@inheritdoc}
     */
    public function __construct(StreamResponse $response)
    {
        $this->streamResponse = $response;

        $headers = array(
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => 'Thu, 19 Nov 1981 08:52:00 GMT',
            'Pragma' => 'no-cache',
            'Content-Type' => 'application/x-amf'
        );

        parent::__construct('', 200, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->setContent('');

        return parent::__toString() . $this->streamResponse->getResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function sendContent()
    {
        return;
    }

    /**
     * Sends stream content for the current web response.
     */
    public function sendStreamContent()
    {
        echo $this->streamResponse->getResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendStreamContent();
    }

    /**
     * @return StreamResponse
     */
    public function getStreamResponse()
    {
        return $this->streamResponse;
    }
}