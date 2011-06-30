<?php

namespace Tecbot\AMFBundle\HttpFoundation;

use Symfony\Component\HttpFoundation\Response as BaseResponse;

class Response extends BaseResponse
{
    /**
     * Constructor.
     *
     * @param string $content The response content
     */
    public function __construct($content)
    {
        parent::__construct($content, 200, array(
                'Cache-Control' => 'no-cache, must-revalidate',
                'Expires' => 'Thu, 19 Nov 1981 08:52:00 GMT',
                'Pragma' => 'no-cache',
                'Content-Type' => 'application/x-amf'
            )
        );
    }
}
