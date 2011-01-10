<?php

namespace Bundle\Tecbot\AMFBundle\Listener;

use Bundle\Tecbot\AMFBundle\Server;
use Bundle\Tecbot\AMFBundle\Service\Resolver\ServiceResolverInterface;
use Symfony\Bundle\FrameworkBundle\EventDispatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

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

    /**
     * Registers a core.request listener.
     *
     * @param EventDispatcher $dispatcher An EventDispatcher instance
     * @param integer         $priority   The priority
     */
    public function register(EventDispatcher $dispatcher, $priority = 0)
    {
        $this->dispatcher = $dispatcher;
        $this->dispatcher->connect('core.request', array($this, 'handle'), $priority);
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