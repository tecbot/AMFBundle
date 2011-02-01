<?php

namespace Tecbot\AMFBundle\Amf;

/**
 * RequestBody.
 *
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
class RequestBody
{
    protected $method;
    protected $arguments;
    protected $source;

    /**
     * Constructor.
     * 
     * @param string $method
     * @param array  $arguments
     * @param string $source
     */
    public function __construct($method, array $arguments = array(), $source = null)
    {
        $this->method = $method;
        $this->arguments = $arguments;
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }
}