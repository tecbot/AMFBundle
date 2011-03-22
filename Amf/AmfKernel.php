<?php

namespace Tecbot\AMFBundle\Amf;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request as BaseRequest;
use Symfony\Component\HttpKernel\Events;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tecbot\AMFBundle\Amf\Event\FilterServiceEvent;
use Tecbot\AMFBundle\Amf\Event\GetBodyResponseEvent;
use Tecbot\AMFBundle\Amf\Service\ServiceResolverInterface;
use Zend\Amf\Constants;
use Zend\Amf\Parser\TypeLoader;
use Zend\Amf\Request\StreamRequest;
use Zend\Amf\Response\StreamResponse;
use Zend\Amf\Value\MessageBody;
use Zend\Amf\Value\Messaging\AcknowledgeMessage;
use Zend\Amf\Value\Messaging\CommandMessage;
use Zend\Amf\Value\Messaging\ErrorMessage;
use Zend\Amf\Value\Messaging\RemotingMessage;

/**
 * AmfKernel.
 *
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
class AmfKernel implements HttpKernelInterface
{
    protected $dispatcher;
    protected $resolver;
    protected $debug;

    /**
     * Constructor
     *
     * @param EventDispatcherInterface    $dispatcher An EventDispatcherInterface instance
     * @param ControllerResolverInterface $resolver   A ControllerResolverInterface instance
     * @param Boolean                     $debug      Debug mode
     * @param array                       $mappings   An array of mapped classes
     */
    public function __construct(EventDispatcherInterface $dispatcher, ServiceResolverInterface $resolver, $debug = false, array $mappings = array())
    {
        $this->dispatcher = $dispatcher;
        $this->resolver = $resolver;
        $this->debug = $debug;

        foreach ($mappings as $alias => $mapping) {
            TypeLoader::setMapping($alias, $mapping['class']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handle(BaseRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        try {
            return $this->handleRaw($request, $type);
        } catch (\Exception $e) {
            if (false === $catch) {
                throw $e;
            }

            return $this->handleException($e, $request, $type);
        }
    }

    /**
     * Handles a stream request to convert it to a response.
     *
     * Exceptions are not caught.
     *
     * @param  Request $request A Request instance
     * @param  integer $type    The type of the request (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     *
     * @return Response         A Response instance
     *
     * @throws \LogicException If one of the listener does not behave as expected
     * @throws NotFoundHttpException When service cannot be found
     */
    protected function handleRaw(Request $request, $type = HttpKernelInterface::MASTER_REQUEST)
    {
        // Get the stream request of the request
        $streamRequest = $request->getStreamRequest();

        // Get the object encoding of the request.
        $objectEncoding = $streamRequest->getObjectEncoding();

        // create a stream response object to place the output from the services.
        $streamResponse = new StreamResponse();

        // set response encoding
        $streamResponse->setObjectEncoding($objectEncoding);

        // Iterate through each of the service calls in the AMF request
        $bodies = $streamRequest->getAmfBodies();
        foreach ($bodies as $body) {
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
                        $return = $this->handleBody($request, new RequestBody($method, $body->getData(), $source), $type);
                    } else {
                        throw new NotFoundHttpException(sprintf('Unable to find the AMF service. targetURI: %s, service: %s', $targetURI, $source));
                    }
                } else {
                    // AMF3 read message type
                    $message = $body->getData();
                    if ($message instanceof CommandMessage) {
                        // async call with command message
                        $return = $this->loadCommandMessage($message);
                    } elseif ($message instanceof RemotingMessage) {
                        $return = new AcknowledgeMessage($message);
                        $return->body = $this->handleBody($request, new RequestBody($message->operation, $message->body, $message->source), $type);
                    } else {
                        // Amf3 message sent with netConnection
                        $targetURI = $body->getTargetURI();

                        // Split the target string into its values.
                        $source = substr($targetURI, 0, strrpos($targetURI, '.'));

                        if ($source) {
                            // Break off method name from namespace into source
                            $method = substr(strrchr($targetURI, '.'), 1);
                            $return = $this->handleBody($request, new RequestBody($method, $body->getData(), $source), $type);
                        } else {
                            throw new NotFoundHttpException(sprintf('Unable to find the AMF service. targetURI: %s, service: %s', $targetURI, $source));
                        }
                    }
                }
                $responseType = Constants::RESULT_METHOD;
            } catch (\Exception $e) {
                $return = $this->errorMessage($objectEncoding, $message, $e->getMessage(), $e->getTraceAsString(), $e->getCode(), $e->getLine());
                $responseType = Constants::STATUS_METHOD;
            }

            $responseURI = $body->getResponseURI() . $responseType;
            $newBody = new MessageBody($responseURI, null, $return);
            $streamResponse->addAmfBody($newBody);
        }

        $response = new Response($streamResponse);
        $response = $this->filterResponse($response, $request, $type);

        $response->getStreamResponse()->finalize();

        return $response;
    }

    /**
     * Loads a remote class and executes the function and returns
     * the result.
     *
     * @param  Request     $request     A Request instance
     * @param  RequestBody $requestBody A RequestBody instance
     * @param  integer     $type        The type of the request (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     *
     * @return mixed                    the result of executing the method
     */
    protected function handleBody(Request $request, RequestBody $requestBody, $type = HttpKernelInterface::MASTER_REQUEST)
    {
        // Set the current request body
        $request->setRequestBody($requestBody);

        // request
        $event = new GetBodyResponseEvent($this, $request, $type, $requestBody);
        $this->dispatcher->dispatch('tecbot_amf.body_request', $event);

        if ($event->hasBodyResponse()) {
            return $event->getBodyResponse();
        }

        // load service
        if (false === $service = $this->resolver->getService($request)) {
            throw new NotFoundHttpException(sprintf('Unable to find the AMF service. method: %s, arguments: %s, source: %s', $requestBody->getMethod(), var_export($requestBody->getArguments(), true), $requestBody->getSource()));
        }

        $event = new FilterServiceEvent($this, $service, $request, $type);
        $this->dispatcher->dispatch('tecbot_amf.service', $event);
        $service = $event->getService();

        // service must be a callable
        if (!is_callable($service)) {
            throw new \LogicException(sprintf('The AMF service must be a callable (%s).', var_export($service, true)));
        }

        // service arguments
        $arguments = $this->resolver->getArguments($request, $service);

        // call service
        $retval = call_user_func_array($service, $arguments);

        return $retval;
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
     * Filters a response object.
     *
     * @param Response  $response A Response instance
     * @param Request   $request  A Request instance
     * @param integer   $type     The type of the request (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     *
     * @return Response           The filtered Response instance
     */
    protected function filterResponse(Response $streamResponse, Request $request, $type)
    {
        $event = new FilterResponseEvent($this, $request, $type, $streamResponse);

        $this->dispatcher->dispatch('tecbot_amf.response', $event);

        return $event->getResponse();
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
                $return = array(
                    'code' => $code,
                );
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

    /**
     * Handles and exception by trying to convert it to a Response.
     *
     * @param  \Exception $e       An \Exception instance
     * @param  Request    $request A Request instance
     * @param  integer    $type    The type of the request (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     *
     * @return Response A Response instance
     */
    protected function handleException(\Exception $e, $request, $type)
    {
        $event = new GetResponseForExceptionEvent($this, $request, $type, $e);
        $this->dispatcher->dispatch(Events::onCoreException, $event);

        if (!$event->hasResponse()) {
            throw $e;
        }

        return $this->filterResponse($event->getResponse(), $request, $type);
    }
}