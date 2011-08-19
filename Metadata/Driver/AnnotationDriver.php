<?php

namespace Tecbot\AMFBundle\Metadata\Driver;

use Doctrine\Common\Annotations\Reader;

use JMS\SerializerBundle\Metadata\Driver\AnnotationDriver as BaseAnnotationDriver;

use Metadata\Driver\DriverInterface;

use Tecbot\AMFBundle\Annotation\VOClass;
use Tecbot\AMFBundle\Metadata\ClassMetadata;

class AnnotationDriver extends BaseAnnotationDriver
{
    private $reader;

    public function __construct(Reader $reader)
    {
        parent::__construct($reader);

        $this->reader = $reader;
    }

    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $baseClassMetadata = parent::loadMetadataForClass($class);

        $classMetadata = new ClassMetadata($name = $class->getName());
        $classMetadata->merge($baseClassMetadata);

        foreach ($this->reader->getClassAnnotations($class) as $annot) {
            if ($annot instanceof VOClass) {
                $classMetadata->voClass = $annot->class;
            }
        }

        return $classMetadata;
    }
}