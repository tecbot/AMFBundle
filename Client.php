<?php

namespace Tecbot\AMFBundle;

use Symfony\Bundle\FrameworkBundle\Client as BaseClient;
use Tecbot\AMFBundle\Amf\Request;
use Zend\Amf\Constants;
use Zend\Amf\Request\StreamRequest;
use Zend\Amf\Value\MessageBody;

class Client extends BaseClient
{
    protected $amfRequest;
    protected $amfResponse;

    public function getAmfResponse()
    {
        return $this->amfResponse;
    }

    public function getAmfRequest()
    {
        return $this->amfRequest;
    }

    public function amfRequest($service, $data)
    {
        $body = new MessageBody($service, "/1", array($data));

        $request = new Request();
        $streamRequest = new StreamRequest();
        $streamRequest->setObjectEncoding(Constants::AMF3_OBJECT_ENCODING);
        $streamRequest->addAmfBody($body);

        $request->setStreamRequest($streamRequest);

        $this->amfRequest = $this->filterAmfRequest($request);

        if ($this->insulated) {
            $this->amfResponse = $this->doAmfRequestInProcess($this->amfRequest);
        } else {
            $this->amfResponse = $this->doAmfRequest($this->amfRequest);
        }

        $response = $this->filterAmfResponse($this->amfResponse);

        return $response;
    }

    protected function doAmfRequestInProcess($request)
    {
        $process = new PhpProcess($this->getAmfScript($request));
        $process->run();

        if (!$process->isSuccessful() || !preg_match('/^O\:\d+\:/', $process->getOutput())) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return unserialize($process->getOutput());
    }

    protected function doAmfRequest($request)
    {
        $this->kernel->shutdown();

        return $this->kernel->handleAmf($request);
    }

    protected function filterAmfRequest($request)
    {
        return $request;
    }

    protected function filterAmfResponse($response)
    {
        $amfBodies = $response->getStreamResponse()->getAmfBodies();

        return $amfBodies[0]->getData();
    }

    protected function getAmfScript($request)
    {
        $kernel = serialize($this->kernel);
        $request = serialize($request);

        $r = new \ReflectionObject($this->kernel);
        $path = $r->getFileName();

        return <<<EOF
<?php

require_once '$path';

\$kernel = unserialize('$kernel');
\$kernel->boot();
echo serialize(\$kernel->handleAmf(unserialize('$request')));
EOF;
    }
}
