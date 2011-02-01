<?php

namespace Tecbot\AMFBundle\Amf;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
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
class AmfKernel implements AmfKernelInterface
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
     * @param array                       $mapping    An array of mapped classes
     */
    public function __construct(EventDispatcherInterface $dispatcher, ServiceResolverInterface $resolver, $debug = false, array $mapping = array())
    {
        $this->dispatcher = $dispatcher;
        $this->resolver = $resolver;
        $this->debug = $debug;

        foreach ($mapping as $asClass => $phpClass) {
            TypeLoader::setMapping($asClass, $phpClass);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request)
    {
        if ('application/x-amf' !== $request->server->get('CONTENT_TYPE')) {
            // No AMF request
            return new BaseResponse('', 403);
        }

        try {
            $response = $this->handleRaw($request);
        } catch (\Exception $e) {
            // Handle any errors in the serialization and service calls.
            // TODO: Create AMF Response
            throw new \RuntimeException(sprintf('Handle error: %s (%d)', $e->getMessage(), $e->getLine()), 0, $e);
        }

        return $response;
    }

    /**
     * Handles a stream request to convert it to a response.
     *
     * Exceptions are not caught.
     *
     * @param  Request $request A Request instance
     *
     * @return Response         A Response instance
     *
     * @throws \LogicException If one of the listener does not behave as expected
     * @throws NotFoundHttpException When service cannot be found
     */
    protected function handleRaw(Request $request)
    {
        // Get the stream request of the request
        $streamRequest = $request->getStreamRequest();

        // Get the object encoding of the request.
        $objectEncoding = $streamRequest->getObjectEncoding();

        // create a stream response object to place the output from the services.
        $response = new StreamResponse();

        // set response encoding
        $response->setObjectEncoding($objectEncoding);

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
                        $return = $this->handleBody($request, new RequestBody($method, $body->getData(), $source));
                    } else {
                        throw new NotFoundHttpException(sprintf('Unable to find the AMF service (AMF0). targetURI: %s, service: %s', $targetURI, $source));
                    }
                } else {
                    // AMF3 read message type
                    $message = $body->getData();
                    if ($message instanceof CommandMessage) {
                        // async call with command message
                        $return = $this->loadCommandMessage($message);
                    } elseif ($message instanceof RemotingMessage) {
                        $return = new AcknowledgeMessage($message);
                        $return->body = $this->handleBody($request, new RequestBody($message->operation, $message->body, $message->source));
                    } else {
                        // Amf3 message sent with netConnection
                        $targetURI = $body->getTargetURI();

                        // Split the target string into its values.
                        $source = substr($targetURI, 0, strrpos($targetURI, '.'));

                        if ($source) {
                            // Break off method name from namespace into source
                            $method = substr(strrchr($targetURI, '.'), 1);
                            $return = $this->handleBody($request, new RequestBody($method, $body->getData(), $source));
                        } else {
                            throw new NotFoundHttpException(sprintf('Unable to find the AMF service (NetConnection). targetURI: %s, service: %s', $targetURI, $source));
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
            $response->addAmfBody($newBody);
        }

        // serialize the stream response
        $response = new Response($response->finalize());

        // Filters the response object.
//        $response = $this->dispatcher->filter(new Event($this, 'core.response', array('request_type' => HttpKernelInterface::MASTER_REQUEST, 'request' => $request)), $response);
//
//        if (!$response instanceof Response) {
//            throw new \RuntimeException('A "core.response" listener returned a non response object.');
//        }

        return $response;
    }

    /**
     * Loads a remote class and executes the function and returns
     * the result.
     *
     * @param  RequestBody $requestBody A RequestBody instance
     * 
     * @return mixed                    the result of executing the method
     */
    protected function handleBody(Request $request, RequestBody $requestBody)
    {
        // Set the current request body
        $request->setRequestBody($requestBody);

        // request
        $event = new Event($this, 'amf.request', array('request_type' => HttpKernelInterface::MASTER_REQUEST, 'request' => $request));
        $ret = $this->dispatcher->notifyUntil($event);
        if ($event->isProcessed()) {
            return $ret;
        }

        // load service
        if (false === $service = $this->resolver->getService($request)) {
            throw new NotFoundHttpException(sprintf('Unable to find the AMF service. method: %s, arguments: %s, source: %s', $requestBody->getMethod(), var_export($requestBody->getArguments(), true), $requestBody->getSource()));
        }

        $event = new Event($this, 'amf.service', array('request_type' => HttpKernelInterface::MASTER_REQUEST, 'request' => $request));
        $service = $this->dispatcher->filter($event, $service);

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
}