<?php

namespace Tecbot\AMFBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Tecbot\AMFBundle\DependencyInjection\Compiler\SetMetadataDriverPass;

/**
 * TecbotAMFBundle.
 *
 * @author Thomas Adam <thomas.adam@tebot.de>
 */
class TecbotAMFBundle extends Bundle
{
    public function build(ContainerBuilder $builder)
    {
        $builder->addCompilerPass(new SetMetadataDriverPass());
    }
}