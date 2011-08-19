<?php

namespace Tecbot\AMFBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class SetMetadataDriverPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('jms_serializer.metadata_factory') || !$container->hasDefinition('tecbot_amf.metadata.lazy_loading_driver')) {
            return;
        }

        $container
                ->getDefinition('jms_serializer.metadata_factory')
                ->replaceArgument(0, new Reference('tecbot_amf.metadata.lazy_loading_driver'));

        // Custom Handlers
        $serializationHandlers = array();
        foreach ($container->findTaggedServiceIds('jms_serializer.serialization_handler') as $id => $attributes) {
            $serializationHandlers[] = new Reference($id);
        }

        $deserializationHandlers = array();
        foreach ($container->findTaggedServiceIds('jms_serializer.deserialization_handlers') as $id => $attributes) {
            $deserializationHandlers[] = new Reference($id);
        }

        $container
            ->getDefinition('tecbot_amf.serialization.visitor.vo')
            ->replaceArgument(1, $serializationHandlers)
        ;
        $container
            ->getDefinition('tecbot_amf.serialization.visitor.vo')
            ->replaceArgument(1, $serializationHandlers)
        ;

        // Property Custom Handlers
        $serializationHandlers = array();
        foreach ($container->findTaggedServiceIds('jms_serializer.property_serialization_handler') as $id => $attributes) {
            $serializationHandlers[] = new Reference($id);
        }

        $deserializationHandlers = array();
        foreach ($container->findTaggedServiceIds('jms_serializer.property_deserialization_handlers') as $id => $attributes) {
            $deserializationHandlers[] = new Reference($id);
        }

        $container
            ->getDefinition('tecbot_amf.serialization.visitor.vo')
            ->replaceArgument(2, $serializationHandlers)
        ;
        $container
            ->getDefinition('tecbot_amf.serialization.visitor.vo')
            ->replaceArgument(2, $serializationHandlers)
        ;
    }
}