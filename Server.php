<?php

namespace Bundle\Tecbot\AMFBundle;

use Bundle\Tecbot\AMFBundle\Service\Resolver\ServiceResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Zend\Amf\Constants;
use Zend\Amf\Request\HttpRequest;
use Zend\Amf\Request\StreamRequest;
use Zend\Amf\Response\HttpResponse;
use Zend\Amf\Response\StreamResponse;
use Zend\Amf\Value;

class Server
{
    protected $resolver;
    protected $request;
    protected $response;
    protected $services = array();
    protected $classMap = array();

    /**
     * Constructor.
     * 
     * @param ServiceResolverInterface $resolver
     * @param array $options
     * @param array $services
     * @param array $classMap 
     */
    public function __construct(ServiceResolverInterface $resolver, array $options = array(), array $services = array(), array $classMap = array())
    {
        $this->resolver = $resolver;

        $this->options = array(
            'debug' => false,
        );

        // check option names
        if ($diff = array_diff(array_keys($options), array_keys($this->options))) {
            throw new \InvalidArgumentException(sprintf('The AMF Server does not support the following options: \'%s\'.', implode('\', \'', $diff)));
        }

        $this->options = array_merge($this->options, $options);

        foreach ($services as $id => $serviceClass) {
            $this->addService($id, $serviceClass);
        }

        foreach ($classMap as $asClass => $phpClass) {
            $this->addClassMap($asClass, $phpClass);
        }
    }

    /**
     * Handle an AMF call from the gateway.
     *
     * @param  null|Zend\Amf\Request\StreamRequest $request Optional
     * 
     * @return Zend\Amf\Response\StreamResponse
     */
    public function handle(StreamRequest $request = null)
    {
        $this->request = null === $request ? $this->getRequest() : $request;

        try {
            $this->handleRequest($this->request);
            $response = $this->getResponse();
        } catch (\Exception $e) {
            // Handle any errors in the serialization and service  calls.
            throw new \RuntimeException('Handle error: ' . $e->getMessage() . ' ' . $e->getLine(), 0, $e);
        }

        // Return the Amf serialized output string
        return $response;
    }

    /**
     * Returns the request.
     * 
     * @return Zend\Amf\Request\StreamRequest
     */
    public function getRequest()
    {
        if (null === $this->request) {
            $this->request = new HttpRequest();
        }

        return $this->request;
    }

    /**
     * Sets the request.
     * 
     * @param $request Zend\Amf\Request\StreamRequest
     */
    public function setRequest(StreamRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Returns the response.
     * 
     * @return Zend\Amf\Response\StreamResponse
     */
    public function getResponse()
    {
        if (null === $this->response) {
            $this->response = new HttpResponse();
        }

        return $this->response;
    }

    /**
     * Sets the response.
     * 
     * @param $request Zend\Amf\Response\StreamResponse
     */
    public function setResponse(StreamResponse $response)
    {
        $this->response = $response;
    }

    /**
     * Returns all mapped AMF services.
     * 
     * @return array
     */
    public function allServices()
    {
        return $this->services;
    }

    /**
     * Returns a mapped AMF service.
     * 
     * @param string $id 
     */
    public function getService($id)
    {
        if (false === isset($this->services[$id])) {
            throw new \InvalidArgumentError(sprintf('AMF service with id %s not found', $id));
        }

        $this->services[$id];
    }

    /**
     * Add a AMF service mapping.
     * 
     * @param string $id
     * @param string $serviceClass 
     */
    public function addService($id, $serviceClass)
    {
        $this->services[$id] = $serviceClass;
    }

    /**
     * Removes a AMF service mapping.
     * 
     * @param string $id
     */
    public function removeService($id)
    {
        if (false === isset($this->services[$id])) {
            return;
        }

        unset($this->services[$id]);
    }

    /**
     * Map ActionScript classes to PHP classes.
     *
     * @param  string $asClass
     * @param  string $phpClass
     */
    public function addClassMap($asClass, $phpClass)
    {
        $this->classMap[$asClass] = $phpClass;
        Parser\TypeLoader::setMapping($asClass, $phpClass);
    }

    /**
     * Returns the Map for ActionScript classes to PHP classes.
     * 
     * @return array
     */
    public function getClassMap()
    {
        return Parser\TypeLoader::$classMap;
    }

    /**
     * Takes the deserialized AMF request and performs any operations.
     *
     * @param  Zend\Amf\Request\StreamRequest $request
     */
    protected function handleRequest(StreamRequest $request)
    {
        // Get the object encoding of the request.
        $objectEncoding = $request->getObjectEncoding();

        // create a response object to place the output from the services.
        $response = $this->getResponse();

        // set response encoding
        $response->setObjectEncoding($objectEncoding);

        $responseBody = $request->getAmfBodies();
        // Iterate through each of the service calls in the AMF request
        foreach ($responseBody as $body) {
            try {
                if ($objectEncoding == Constants::AMF0_OBJECT_ENCODING) {
                    // AMF0 Object Encoding
                    $targetURI = $body->getTargetURI();
                    $message = '';

                    // Split the target string into its values.
                    $source = substr($targetURI, 0, strrpos($targetURI, '.'));

                    if ($source) {
                        // Break off method name from namespace into source
                        $method = substr(strrchr($targetURI, '.'), 1);
                        $return = $this->dispatch($method, $body->getData(), $source);
                    } else {
                        throw new NotFoundHttpException('Unable to find the AMF service.');
                    }
                } else {
                    // AMF3 read message type
                    $message = $body->getData();
                    if ($message instanceof Value\Messaging\CommandMessage) {
                        // async call with command message
                        $return = $this->loadCommandMessage($message);
                    } elseif ($message instanceof Value\Messaging\RemotingMessage) {
                        $return = new Value\Messaging\AcknowledgeMessage($message);
                        $return->body = $this->dispatch($message->operation, $message->body, $message->source);
                    } else {
                        // Amf3 message sent with netConnection
                        $targetURI = $body->getTargetURI();

                        // Split the target string into its values.
                        $source = substr($targetURI, 0, strrpos($targetURI, '.'));

                        if ($source) {
                            // Break off method name from namespace into source
                            $method = substr(strrchr($targetURI, '.'), 1);
                            $return = $this->dispatch($method, $body->getData(), $source);
                        } else {
                            throw new NotFoundHttpException('Unable to find the AMF service.');
                        }
                    }
                }
                $responseType = Constants::RESULT_METHOD;
            } catch (\Exception $e) {
                $return = $this->errorMessage($objectEncoding, $message, $e->getMessage(), $e->getTraceAsString(), $e->getCode(), $e->getLine());
                $responseType = Constants::STATUS_METHOD;
            }

            $responseURI = $body->getResponseURI() . $responseType;
            $newBody = new Value\MessageBody($responseURI, null, $return);
            $response->addAmfBody($newBody);
        }

        // serialize the response and return serialized body.
        $response->finalize();
    }

    /**
     * Loads a remote class or method and executes the function and returns
     * the result.
     *
     * @param  string $method Is the method to execute
     * @param  mixed $params values for the method
     * @param  string $source
     * 
     * @return mixed $response the result of executing the method
     */
    protected function dispatch($method, array $params = null, $source = null)
    {
        // load service
        if (false === isset($this->services[$source]) || false === $service = $this->resolver->getService($this->services[$source], $method)) {
            throw new NotFoundHttpException('Unable to find the AMF service.');
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
     * @param  Zend\Amf\Value\Messaging\CommandMessage $message
     * 
     * @return Zend\Amf\Value\Messaging\AcknowledgeMessage
     */
    protected function loadCommandMessage(Value\Messaging\CommandMessage $message)
    {
        switch ($message->operation) {
            case Value\Messaging\CommandMessage::DISCONNECT_OPERATION :
            case Value\Messaging\CommandMessage::CLIENT_PING_OPERATION :
                $return = new Value\Messaging\AcknowledgeMessage($message);
                break;
            default :
                throw new \RuntimeException('CommandMessage::' . $message->operation . ' not implemented');
                break;
        }

        return $return;
    }

    /**
     * Create appropriate error message.
     *
     * @param int $objectEncoding Current AMF encoding
     * @param string $message Message that was being processed when error happened
     * @param string $description Error description
     * @param mixed $detail Detailed data about the error
     * @param int $code Error code
     * @param int $line Error line
     * 
     * @return Zend\Amf\Value\Messaging\ErrorMessage|array
     */
    protected function errorMessage($objectEncoding, $message, $description, $detail, $code, $line)
    {
        $return = null;
        switch ($objectEncoding) {
            case Constants::AMF0_OBJECT_ENCODING :
                $return = array(
                    'code' => $code,
                );
                if (false !== $this->options['debug']) {
                    $return['description'] = $description;
                    $return['detail'] = $detail;
                    $return['line'] = $line;
                }

                break;
            case Constants::AMF3_OBJECT_ENCODING :
                $return = new Value\Messaging\ErrorMessage($message);
                $return->faultCode = $code;
                if (false !== $this->options['debug']) {
                    $return->faultString = $description;
                    $return->faultDetail = $detail;
                }

                break;
        }

        return $return;
    }
}