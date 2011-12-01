<?php

namespace Tecbot\AMFBundle\Amf;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Tecbot\AMFBundle\Amf\AmfEvents;
use Tecbot\AMFBundle\Amf\Service\ServiceResolverInterface;
use Tecbot\AMFBundle\Event\FilterBodyResponseEvent;
use Tecbot\AMFBundle\Event\FilterServiceEvent;
use Tecbot\AMFBundle\Event\GetBodyResponseEvent;
use Tecbot\AMFBundle\Event\GetBodyResponseForExceptionEvent;
use Tecbot\AMFBundle\HttpFoundation\Response;
use Zend\Amf\Constants;
use Zend\Amf\Parser\TypeLoader;
use Zend\Amf\Request\StreamRequest;
use Zend\Amf\Response\StreamResponse;
use Zend\Amf\Value\MessageBody;
use Zend\Amf\Value\Messaging\AcknowledgeMessage;
use Zend\Amf\Value\Messaging\CommandMessage;
use Zend\Amf\Value\Messaging\ErrorMessage;
use Zend\Amf\Value\Messaging\RemotingMessage;

class Server
{
    private $dispatcher;
    private $container;
    private $resolver;
    private $debug;
    private $log = array();

    /**
     * Constructor
     *
     * @param EventDispatcherInterface    $dispatcher An EventDispatcherInterface instance
     * @param ContainerInterface          $container  A ContainerInterface instance
     * @param ControllerResolverInterface $resolver   A ControllerResolverInterface instance
     * @param Boolean                     $debug      Debug mode
     * @param array                       $mappings   An array of mapped classes
     */
    public function __construct(EventDispatcherInterface $dispatcher, ContainerInterface $container, ServiceResolverInterface $resolver, $debug = false, array $mappings = array())
    {
        $this->dispatcher = $dispatcher;
        $this->container = $container;
        $this->resolver = $resolver;
        $this->debug = $debug;

        foreach ($mappings as $alias => $mapping) {
            TypeLoader::setMapping($alias, $mapping['class']);
        }
    }

    /**
     * @param  StreamRequest $request
     *
     * @return Response
     *
     * @throws \RuntimeException
     */
    public function handle(StreamRequest $request = null)
    {
        if (null === $request) {
            $content = $this->container->get("request")->getContent();
            if (empty($content)) {
                return new BaseResponse("<p>Symfony Amf Endpoint</p>");
            }
            $request = new StreamRequest();
            $request->initialize($content);
        }

        // Check for errors that may have happend in deserialization of Request.
        try {
            // Take converted PHP objects and handle service call.
            // Serialize to Zend_Amf_response for output stream
            $response = $this->handleRaw($request);
        } catch (\Exception $e) {
            // Handle any errors in the serialization and service  calls.
            throw new \RuntimeException('Handle error: ' . $e->getMessage() . ' ' . $e->getLine(), 0, $e);
        }

        // Return the Amf serialized Response
        return new Response($response->finalize());
    }

    /**
     * Adds a log message.
     *
     * @param string $message
     */
    public function addLogMessage($message)
    {
        $this->log[] = $message;
    }

    protected function handleRaw(StreamRequest $request)
    {
        // Get the object encoding of the request.
        $objectEncoding = $request->getObjectEncoding();

        // create a stream response object to place the output from the services.
        $response = new StreamResponse();

        // set response encoding
        $response->setObjectEncoding($objectEncoding);

        // Iterate through each of the service calls in the AMF request
        $bodies = $request->getAmfBodies();
        foreach ($bodies as $body) {
            $message = null;
            /* @var $body \Zend\Amf\Value\MessageBody */
            try {
                if (Constants::AMF0_OBJECT_ENCODING === $objectEncoding) {
                    // AMF0 Object Encoding
                    $targetURI = $body->getTargetURI();
                    $message = '';

                    // Split the target string into its values.
                    $source = substr($targetURI, 0, strrpos($targetURI, '.'));

                    if ($source) {
                        // Break off method name from namespace into source
                        $method = substr(strrchr($targetURI, '.'), 1);
                        $return = $this->handleBodyRequest(new BodyRequest($method, $body->getData(), $source));
                    } else {
                        throw new \RuntimeException(sprintf('Unable to find the Amf service. targetURI: %s, service: %s', $targetURI, $source));
                    }
                } else {
                    // AMF3 read message type
                    $message = $body->getData();
                    if ($message instanceof CommandMessage) {
                        // async call with command message
                        $return = $this->loadCommandMessage($message);
                    } elseif ($message instanceof RemotingMessage) {
                        $return = new AcknowledgeMessage($message);
                        $return->body = $this->handleBodyRequest(new BodyRequest($message->operation, $message->body, $message->source));
                    } else {
                        // Amf3 message sent with netConnection
                        $targetURI = $body->getTargetURI();

                        // Split the target string into its values.
                        $source = substr($targetURI, 0, strrpos($targetURI, '.'));

                        if ($source) {
                            // Break off method name from namespace into source
                            $method = substr(strrchr($targetURI, '.'), 1);
                            $return = $this->handleBodyRequest(new BodyRequest($method, $body->getData(), $source));
                        } else {
                            throw new \RuntimeException(sprintf('Unable to find the Amf service. targetURI: %s, service: %s', $targetURI, $source));
                        }
                    }
                }
                $responseType = Constants::RESULT_METHOD;
            } catch (\Exception $e) {
                try {
                    $return = $this->handleException($e, $body);
                    $responseType = Constants::RESULT_METHOD;

                    if (Constants::AMF3_OBJECT_ENCODING === $objectEncoding) {
                        $newMessage = new AcknowledgeMessage($message);
                        $newMessage->body = $return;

                        $return = $newMessage;
                    }
                } catch (\Exception $e) {
                    $return = $this->errorMessage($objectEncoding, $message, $e->getMessage(), $e->getTraceAsString(), $e->getCode(), $e->getLine());
                    $responseType = Constants::STATUS_METHOD;
                }
            }

            if ($return instanceof AcknowledgeMessage) {
                $return->headers->log = $this->log;
            }
            $this->log = array();

            $responseURI = $body->getResponseURI() . $responseType;
            $newBody = new MessageBody($responseURI, null, $return);
            $response->addAmfBody($newBody);
        }

        return $response;
    }

    /**
     * Loads a remote class and executes the function and returns
     * the result.
     *
     * @param  BodyRequest $bodyRequest A BodyRequest instance
     *
     * @return mixed the result of executing the method
     */
    protected function handleBodyRequest(BodyRequest $bodyRequest)
    {
        // request
        $event = new GetBodyResponseEvent($bodyRequest);
        $this->dispatcher->dispatch(AmfEvents::BODY_REQUEST, $event);

        if ($event->hasBodyResponse()) {
            return $event->getBodyResponse();
        }

        // load service
        if (false === $service = $this->resolver->getService($bodyRequest)) {
            throw new \RuntimeException(sprintf('Unable to find the Amf service. method: %s, arguments: %s, source: %s', $bodyRequest->getMethod(), var_export($bodyRequest->getArguments(), true), $bodyRequest->getSource()));
        }

        $event = new FilterServiceEvent($service, $bodyRequest);
        $this->dispatcher->dispatch(AmfEvents::SERVICE, $event);
        $service = $event->getService();

        // service arguments
        $arguments = $this->resolver->getArguments($bodyRequest, $service);

        // call service
        $response = call_user_func_array($service, $arguments);

        return $this->filterBodyResponse($response, $bodyRequest);
    }

    private function filterBodyResponse($response, BodyRequest $bodyRequest)
    {
        $event = new FilterBodyResponseEvent($bodyRequest, $response);

        $this->dispatcher->dispatch(AmfEvents::BODY_RESPONSE, $event);

        return $event->getBodyResponse();
    }

    /**
     * Handles each of the 11 different command message types.
     *
     * A command message is a flex.messaging.messages.CommandMessage
     *
     * @param  CommandMessage     $message A CommandMessage instance
     *
     * @return AcknowledgeMessage          A AcknowledgeMessage instance
     */
    protected function loadCommandMessage(CommandMessage $message)
    {
        switch ($message->operation) {
            case CommandMessage::DISCONNECT_OPERATION:
            case CommandMessage::CLIENT_PING_OPERATION:
                $return = new AcknowledgeMessage($message);
                break;
            default:
                throw new \RuntimeException('CommandMessage::' . $message->operation . ' not implemented');
                break;
        }

        return $return;
    }

    /**
     * Handles and exception by trying to convert it to a Response.
     *
     * @param  \Exception  $e    An Exception instance
     * @param  MessageBody $body A MessageBody instance
     *
     * @return mixed
     */
    protected function handleException(\Exception $e, MessageBody $body)
    {
        $event = new GetBodyResponseForExceptionEvent($body, $e);
        $this->dispatcher->dispatch(AmfEvents::BODY_EXCEPTION, $event);

        if (!$event->hasBodyResponse()) {
            throw $e;
        }

        return $event->getBodyResponse();
    }

    /**
     * Create appropriate error message.
     *
     * @param int    $objectEncoding Current AMF encoding
     * @param string $message        Message that was being processed when error happened
     * @param string $description    Error description
     * @param mixed  $detail         Detailed data about the error
     * @param int    $code           Error code
     * @param int    $line           Error line
     *
     * @return ErrorMessage|array    A ErrorMessage instance or an array
     */
    protected function errorMessage($objectEncoding, $message, $description, $detail, $code, $line)
    {
        $return = null;
        switch ($objectEncoding) {
            case Constants::AMF0_OBJECT_ENCODING :
                $return = array('code' => $code);
                if (false !== $this->debug) {
                    $return['description'] = $description;
                    $return['detail'] = $detail;
                    $return['line'] = $line;
                }

                break;
            case Constants::AMF3_OBJECT_ENCODING :
                $return = new ErrorMessage($message);
                $return->faultCode = $code;
                if (false !== $this->debug) {
                    $return->faultString = $description;
                    $return->faultDetail = $detail;
                }

                break;
        }

        return $return;
    }
}
