<?php

namespace Tecbot\AmfBundle\Metadata\Driver;

use Doctrine\Common\Annotations\Reader;

use Metadata\Driver\DriverInterface;

use Tecbot\AmfBundle\Annotation\Service;
use Tecbot\AMFBundle\Annotation\VO;
use Tecbot\AMFBundle\Metadata\ClassMetadata;

class AnnotationDriver implements DriverInterface
{
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $classMetadata = new ClassMetadata($name = $class->getName());
        $classMetadata->fileResources[] = $class->getFilename();

        foreach ($this->reader->getClassAnnotations($class) as $annot) {
            if ($annot instanceof Service) {
                $classMetadata->isService = true;
                $classMetadata->alias = $annot->alias;
            } else if ($annot instanceof VO) {
                $classMetadata->isVo = true;
                $classMetadata->alias = $annot->alias;
            }
        }

        return $classMetadata;
    }
}