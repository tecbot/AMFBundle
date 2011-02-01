<?php

namespace Tecbot\AMFBundle\Amf;

/**
 * AmfKernelInterface.
 * 
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
interface AmfKernelInterface
{
    /**
     * Handles a AMF Request to convert it to a Response.
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to convert them to a Response instance.
     *
     * @param  Request $request A Request instance
     *
     * @return Response A Response instance
     *
     * @throws \Exception When an Exception occurs during processing
     */
    function handle(Request $request);
}