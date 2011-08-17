<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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