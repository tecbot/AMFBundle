<?php

namespace Tecbot\AMFBundle\Controller;

use Tecbot\AMFBundle\Amf\Server;

class GatewayController
{
    protected $server;

    /**
     * Constructor.
     *
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Handle the Amf request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function gatewayAction()
    {
        return $this->server->handle();
    }
}
