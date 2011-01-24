<?php

namespace Tecbot\AMFBundle\Listener;

use Tecbot\AMFBundle\Server;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class RequestListener
{
    protected $server;
    protected $container;
    protected $lastResponse;
    protected $dispatcher;

    public function __construct(Server $server, LoggerInterface $logger = null)
    {
        $this->server = $server;
        $this->logger = $logger;
    }
    
    public function handle(Event $event)
    {
        $contentType = $event->get('request')->server->get('CONTENT_TYPE');
        if ('application/x-amf' != $contentType) {
            return;
        }
        
        $master = HttpKernelInterface::MASTER_REQUEST === $event->get('request_type');
        if(false === $master) {
            $event->setReturnValue($this->lastResponse);
            
            return true;
        }
        
        $amfResponse = $this->server->handle();

        $this->lastResponse = new Response($amfResponse->getResponse(), 200, array(
                    'Cache-Control' => 'no-cache, must-revalidate',
                    'Expires' => 'Thu, 19 Nov 1981 08:52:00 GMT',
                    'Pragma' => 'no-cache',
                    'Content-Type' => 'application/x-amf',
        ));
        
        $event->setReturnValue($this->lastResponse);
        
        return true;
    }
}