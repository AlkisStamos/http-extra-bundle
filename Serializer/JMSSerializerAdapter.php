<?php

namespace Alks\HttpExtraBundle\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class JMSSerializerAdapter
 * @package Alks\HttpExtraBundle\Serializer
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class JMSSerializerAdapter extends AbstractJMSAdapter implements SerializerInterface
{

    /**
     * @var \JMS\Serializer\SerializerInterface
     */
    private $serializer;

    public function __construct(\JMS\Serializer\SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Serializes data in the appropriate format.
     *
     * @param mixed $data any data
     * @param string $format format name
     * @param array $context options normalizers/encoders have access to
     *
     * @return string
     */
    public function serialize($data, $format, array $context = array())
    {
        return $this->serializer->serialize($data,$format,$this->mergeContext($context,SerializationContext::create()));
    }

    /**
     * Deserializes data into the given type.
     *
     * @param mixed $data
     * @param string $type
     * @param string $format
     * @param array $context
     *
     * @return object
     */
    public function deserialize($data, $type, $format, array $context = array())
    {
        return $this->serializer->deserialize($data,$type,$format,$this->mergeContext($context,DeserializationContext::create()));
    }
}