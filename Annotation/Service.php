<?php

namespace Tecbot\AMFBundle\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Service
{
    public $alias;

    public function __construct(array $values)
    {
        if (!is_string($values['value'])) {
            throw new RuntimeException(sprintf('"value" must be a string.'));
        }

        $this->alias = $values['value'];
    }
}
