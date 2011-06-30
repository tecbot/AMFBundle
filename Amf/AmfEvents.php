<?php

namespace Tecbot\AMFBundle\Amf;

/**
 * Contains all events thrown in the Server component
 *
 * @author Thomas Adam <thomas.adam@tecbot.de>
 */
final class AmfEvents
{
    /**
     * @var string
     */
    const BODY_REQUEST = 'tecbot_amf.body_request';
    /**
     * @var string
     */
    const SERVICE = 'tecbot_amf.service';
    /**
     * @var string
     */
    const BODY_RESPONSE = 'tecbot_amf.body_response';
}
