<?php

namespace Tecbot\AMFBundle\Metadata\Driver;

use JMS\SerializerBundle\Metadata\Driver\XmlDriver as BaseXmlDriver;

use Metadata\Driver\AbstractFileDriver;

use Tecbot\AMFBundle\Metadata\ClassMetadata;

use RuntimeException;

class XmlDriver extends BaseXmlDriver
{
    protected function loadMetadataFromFile(\ReflectionClass $class, $path)
    {
        $baseMetadata = parent::loadMetadataFromFile($class, $path);

        $metadata = new ClassMetadata($name = $class->getName());
        $metadata->merge($baseMetadata);

        $elem = simplexml_load_file($path);
        $elems = $elem->xpath("./class[@name = '".$name."']");
        $elem = reset($elems);

        if (null !== $voClass = $elem->attributes()->{'vo-class'}) {
            $metadata->voClass = (string)$voClass;
        }

        return $metadata;
    }

    protected function getExtension()
    {
        return 'xml';
    }
}