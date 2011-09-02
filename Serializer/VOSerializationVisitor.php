<?php

namespace Tecbot\AMFBundle\Serializer;

use JMS\SerializerBundle\Metadata\ClassMetadata;
use JMS\SerializerBundle\Metadata\PropertyMetadata;
use JMS\SerializerBundle\Serializer\GenericSerializationVisitor;

use Zend\Amf\Value\Messaging\ArrayCollection;


/**
 * @author Thomas Adam <thomas.adam@tecbot.de>
 */
class VOSerializationVisitor extends GenericSerializationVisitor
{
    public function getResult()
    {
        return $this->getRoot();
    }

    public function visitArray($data, $type)
    {
        if (null === $this->root) {
            $this->root = new ArrayCollection();
            $this->root->source = array();
            $rs = &$this->root;
        } else {
            $rs = new ArrayCollection();
            $rs->source = array();
        }

        foreach ($data as $k => $v) {
            $v = $this->navigator->accept($v, null, $this);

            if (null === $v) {
                continue;
            }

            $rs->source[$k] = $v;
        }

        return $rs;
    }

    public function visitProperty(PropertyMetadata $metadata, $data)
    {
        $v = $this->navigator->accept($metadata->reflection->getValue($data), null, $this);
        if (null === $v) {
            return;
        }

        $k = $this->namingStrategy->translateName($metadata);

        if (is_array($this->data)) {
            $this->data[$k] = $v;
        } else {
            $this->data->{$k} = $v;
        }
    }

    public function visitPropertyUsingCustomHandler(PropertyMetadata $metadata, $object)
    {
        $data = $metadata->reflection->getValue($object);
        if (null === $data) {
            return false;
        }

        $type = gettype($data);
        if ('object' === $type) {
            $type = get_class($data);
        }

        $visited = false;
        foreach ($this->propertyCustomHandlers as $handler) {
            $rs = $handler->serialize($this, $data, $type, $visited);
            if ($visited) {
                $k = $this->namingStrategy->translateName($metadata);

                if (is_array($this->data)) {
                    $this->data[$k] = $rs;
                } else {
                    $this->data->{$k} = $rs;
                }

                return true;
            }
        }

        return false;
    }

    public function startVisitingObject(ClassMetadata $metadata, $data, $type)
    {
        if (null === $this->root) {
            $this->root = null === $metadata->voClass ? new \stdClass : new $metadata->voClass;
        }

        $this->dataStack->push($this->data);
        $this->data = null === $metadata->voClass ? array() : new $metadata->voClass;
    }

    public function endVisitingObject(ClassMetadata $metadata, $data, $type)
    {
        $rs = $this->data;
        $this->data = $this->dataStack->pop();

        if ((null !== $metadata->voClass && $this->root instanceof $metadata->voClass) || $this->root instanceof \stdClass && 0 === $this->dataStack->count()) {
            $this->root = $rs;
        }

        return $rs;
    }
}