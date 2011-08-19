<?php

namespace Tecbot\AMFBundle\Metadata\Driver;

use JMS\SerializerBundle\Metadata\Driver\YamlDriver as BaseYamlDriver;

use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;

use Symfony\Component\Yaml\Yaml;

use Tecbot\AMFBundle\Metadata\ClassMetadata;

use RuntimeException;

class YamlDriver extends BaseYamlDriver
{
    protected function loadMetadataFromFile(\ReflectionClass $class, $file)
    {
        $baseClassMetadata = parent::loadMetadataFromFile($class, $file);

        $classMetadata = new ClassMetadata($name = $class->getName());
        $classMetadata->merge($baseClassMetadata);

        $config = Yaml::parse(file_get_contents($file));
        $config = $config[$class->getName()];

        if (isset($config['vo_class'])) {
            $metadata->voClass = (string)$config['vo_class'];
        }

        return $metadata;
    }

    protected function getExtension()
    {
        return 'yml';
    }
}