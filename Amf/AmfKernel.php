<?php

namespace Tecbot\AMFBundle\Amf;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
 * Description of AmfKernel
 *
 * @author Thomas
 */
class AmfKernel implements AmfKernelInterface
{
    protected $dispatcher;
    protected $resolver;
    protected $debug;
    protected $services;

    /**
     * Constructor
     *
     * @param EventDispatcherInterface    $dispatcher An EventDispatcherInterface instance
     * @param ControllerResolverInterface $resolver   A ControllerResolverInterface instance
     * @param array                       $services   An array of services
     * @param array                       $mapping    An array of class mapping
     */
    public function __construct(EventDispatcherInterface $dispatcher, ServiceResolverInterface $resolver, $debug = false, array $services = array(), array $mapping = array())
    {
        $this->dispatcher = $dispatcher;
        $this->resolver = $resolver;
        $this->debug = $debug;

        foreach ($services as $id => $serviceClass) {
            $this->services[$id] = $serviceClass;
        }

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
            $response = $this->handleRaw($request->getStreamRequest());
        } catch (\Exception $e) {
            // Handle any errors in the serialization and service calls.
            throw new \RuntimeException(sprintf('Handle error: %s (%d)', $e->getMessage(), $e->getLine()), 0, $e);
        }

        return $response;
    }

    /**
     * Handles a stream request to convert it to a response.
     *
     * Exceptions are not caught.
     *
     * @param  StreamRequest $request A StreamRequest instance
     *
     * @return Response               A Response instance
     *
     * @throws \LogicException If one of the listener does not behave as expected
     * @throws NotFoundHttpException When service cannot be found
     */
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
                        $return = $this->handleBody($method, $body->getData(), $source);
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
                        $return->body = $this->handleBody($message->operation, $message->body, $message->source);
                    } else {
                        // Amf3 message sent with netConnection
                        $targetURI = $body->getTargetURI();

                        // Split the target string into its values.
                        $source = substr($targetURI, 0, strrpos($targetURI, '.'));

                        if ($source) {
                            // Break off method name from namespace into source
                            $method = substr(strrchr($targetURI, '.'), 1);
                            $return = $this->handleBody($method, $body->getData(), $source);
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

        // serialize the stream response and return a response.
        return new Response($response->finalize());
    }

    /**
     * Loads a remote class and executes the function and returns
     * the result.
     *
     * @param  string $method    Is the method to execute
     * @param  mixed  $params    values for the method
     * @param  string $service   The Service
     * 
     * @return mixed             the result of executing the method
     */
    protected function handleBody($method, array $params = null, $service = null)
    {
        // load service
        if (false === isset($this->services[$service]) || false === $service = $this->resolver->getService($this->services[$service], $method)) {
            throw new NotFoundHttpException(sprintf('Unable to find the AMF service. method: %s, params: %s, source: %s', $method, var_export($params, true), $service));
        }

        // service must be a callable
        if (!is_callable($service)) {
            throw new \LogicException(sprintf('The AMF service must be a callable (%s).', var_export($service, true)));
        }

        if (null === $params) {
            $params = array();
        }

        // service arguments
        $arguments = $this->resolver->getArguments($params, $service);

        // call service
        $returnval = call_user_func_array($service, $arguments);

        return $returnval;
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