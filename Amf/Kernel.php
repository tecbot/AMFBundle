<?php

namespace Tecbot\AMFBundle\Amf;

use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Kernel.
 *
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
abstract class Kernel extends BaseKernel
{
    /**
     * {@inheritdoc}
     */
    public function handleAmf(Request $request)
    {
        if (false === $this->booted) {
            $this->boot();
        }

        return $this->container->get('tecbot_amf.kernel')->handle($request);
    }
}