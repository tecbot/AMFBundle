<?php

namespace Tecbot\AmfBundle\Metadata\Driver;

use Metadata\Driver\AbstractFileDriver;

use Tecbot\AmfBundle\Metadata\ClassMetadata;

use RuntimeException;

class XmlDriver extends AbstractFileDriver
{
    protected function loadMetadataFromFile(\ReflectionClass $class, $path)
    {
        $elem = simplexml_load_file($path);

        $metadata = new ClassMetadata($name = $class->getName());
        if (!$elems = $elem->xpath("./class[@name = '" . $name . "']")) {
            throw new RuntimeException(sprintf('Could not find class %s inside XML element.', $name));
        }
        $elem = reset($elems);

        $metadata->fileResources[] = $path;
        $metadata->fileResources[] = $class->getFileName();

        if ($elem->getName() == 'service') {
            $metadata->isService = true;
        } else if ($elem->getName() == 'vo') {
            $metadata->isVo = true;
        }

        if (isset($elem['alias'])) {
            $class->alias = (string)$elem['alias'];
        }

        return $metadata;
    }

    protected function getExtension()
    {
        return 'xml';
    }
}