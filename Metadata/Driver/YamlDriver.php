<?php

namespace Tecbot\AmfBundle\Metadata\Driver;

use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;

use Symfony\Component\Yaml\Yaml;

use Tecbot\AmfBundle\Metadata\ClassMetadata;

use RuntimeException;

class YamlDriver extends AbstractFileDriver
{
    protected function loadMetadataFromFile(\ReflectionClass $class, $file)
    {
        $config = Yaml::parse(file_get_contents($file));

        if (!isset($config[$name = $class->getName()])) {
            throw new RuntimeException(sprintf('Expected metadata for class %s to be defined in %s.', $class->getName(), $file));
        }

        $config = $config[$name];
        $metadata = new ClassMetadata($name);
        $metadata->fileResources[] = $file;
        $metadata->fileResources[] = $class->getFileName();

        $config['type'] = isset($config['type']) ? $config['type'] : 'vo';

        if ($config['type'] === 'service') {
            $metadata->isService = true;
        } else if ($config['type'] === 'vo') {
            $metadata->isVo = true;
        }

        if (isset($config['alias'])) {
            $class->alias = (string)$config['alias'];
        }

        return $metadata;
    }

    protected function getExtension()
    {
        return 'yml';
    }
}