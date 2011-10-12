<?php

namespace Tecbot\AMFBundle\Logger\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Thomas Adam <thomas.adam@tecbot.de>
 */
class AmfHandler extends AbstractProcessingHandler
{
    protected $container;
    protected $server;

    /**
     * @param integer $level The minimum logging level at which this handler will be triggered
     * @param Boolean $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(ContainerInterface $container, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->container = $container;
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param array $record
     * @return void
     */
    protected function write(array $record)
    {
        if (null === $this->server) {
            $this->server = $this->container->get('tecbot_amf.server');
        }

        $this->server->addLogMessage((string)$record['formatted']);
    }
}
