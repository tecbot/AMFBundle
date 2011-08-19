<?php

namespace Tecbot\AMFBundle\Metadata;

use JMS\SerializerBundle\Metadata\ClassMetadata as BaseClassMetadata;

use Metadata\MergeableInterface;
use Metadata\MethodMetadata;
use Metadata\MergeableClassMetadata;

use InvalidArgumentException;

class ClassMetadata extends BaseClassMetadata
{
    public $voClass;

    public function merge(MergeableInterface $object)
    {
        if (!$object instanceof BaseClassMetadata) {
            throw new InvalidArgumentException('$object must be an instance of ClassMetadata.');
        }
        parent::merge($object);


        if($object instanceof ClassMetadata && null !== $object->voClass) {
            $this->voClass = $object->voClass;
        }
    }

    public function serialize()
    {
        return serialize(array(
            $this->voClass,
            parent::serialize(),
        ));
    }

    public function unserialize($str)
    {
        list(
            $this->voClass,
            $parentStr
        ) = unserialize($str);

        parent::unserialize($parentStr);
    }
}