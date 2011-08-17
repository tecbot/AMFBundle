<?php

namespace Tecbot\AmfBundle\Metadata;

use Metadata\MergeableInterface;
use Metadata\MethodMetadata;
use Metadata\MergeableClassMetadata;

use InvalidArgumentException;

class ClassMetadata extends MergeableClassMetadata
{
    public $isService;
    public $isVo;
    public $alias;

    public function merge(MergeableInterface $object)
    {
        if (!$object instanceof ClassMetadata) {
            throw new InvalidArgumentException('$object must be an instance of ClassMetadata.');
        }
        parent::merge($object);

        $this->isService = $object->isService;
        $this->isVo = $object->isVo;
        $this->alias = $object->alias;
    }

    public function serialize()
    {
        return serialize(array(
            $this->isService,
            $this->isVo,
            $this->alias,
            parent::serialize(),
        ));
    }

    public function unserialize($str)
    {
        list(
            $this->isService,
            $this->isVo,
            $this->alias,
            $parentStr
        ) = unserialize($str);

        parent::unserialize($parentStr);
    }
}