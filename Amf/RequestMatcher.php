<?php

namespace Tecbot\AMFBundle\Amf;

use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Request as BaseRequest;

/**
 * RequestMatcher.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
class RequestMatcher implements RequestMatcherInterface
{
    protected $services;
    protected $methods;
    protected $host;
    protected $ip;

    /**
     * Constructor.
     * 
     * @param string|array $services An array of AMF services
     * @param string|array $methods  An array of methods
     * @param string       $host     The URL host name.
     * @param string       $ip       A specific IP address or a range specified using IP/netmask like 192.168.1.0/24
     */
    public function __construct($services = null, $methods = null, $host = null, $ip = null)
    {
        $this->services = array_map('strtolower', is_array($services) ? $services : array($services));
        $this->methods = array_map('strtolower', is_array($methods) ? $methods : array($methods));
        $this->host = $host;
        $this->ip = $ip;
    }

    /**
     * Adds a check for the AMF service.
     *
     * @param string|array An AMF service or an array of AMF services
     */
    public function matchService($service)
    {
        $this->services = array_map('strtolower', is_array($service) ? $service : array($service));
    }

    /**
     * Adds a check for the AMF method.
     *
     * @param string|array An AMF method or an array of AMF methods
     */
    public function matchMethod($method)
    {
        $this->methods = array_map('strtolower', is_array($method) ? $method : array($method));
    }

    /**
     * Adds a check for the URL host name.
     *
     * @param string $regexp A Regexp
     */
    public function matchHost($regexp)
    {
        $this->host = $regexp;
    }

    /**
     * Adds a check for the client IP.
     *
     * @param string $ip A specific IP address or a range specified using IP/netmask like 192.168.1.0/24
     */
    public function matchIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * {@inheritdoc}
     */
    public function matches(BaseRequest $request)
    {
        $requestBody = $request->getRequestBody();

        if (null !== $this->methods && !in_array(strtolower($requestBody->getMethod()), $this->methods)) {
            return false;
        }

        if (null !== $this->services && !in_array(strtolower($requestBody->getSource()), $this->services)) {
            return false;
        }

        if (null !== $this->host && !preg_match('#^' . $this->host . '$#', $request->getHost())) {
            return false;
        }

        if (null !== $this->ip && !$this->checkIp($request->getClientIp())) {
            return false;
        }

        return true;
    }

    /**
     * @param  string  $ip A specific IP address or a range specified using IP/netmask like 192.168.1.0/24
     * 
     * @return Boolean
     */
    protected function checkIp($ip)
    {
        if (false !== strpos($this->ip, '/')) {
            list($address, $netmask) = explode('/', $this->ip);

            if ($netmask < 1 || $netmask > 32) {
                return false;
            }
        } else {
            $address = $this->ip;
            $netmask = 32;
        }

        return 0 === substr_compare(sprintf('%032b', ip2long($ip)), sprintf('%032b', ip2long($address)), 0, $netmask);
    }
}