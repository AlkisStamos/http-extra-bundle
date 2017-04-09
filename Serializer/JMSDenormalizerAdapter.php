<?php

namespace Alks\HttpExtraBundle\Serializer;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Class JMSDenormalizerAdapter
 * @package Alks\HttpExtraBundle\Serializer
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class JMSDenormalizerAdapter extends AbstractJMSAdapter implements DenormalizerInterface
{
    /**
     * @var Serializer
     */
    private $denormalizer;

    /**
     * NOTICE: if an upgraded version of the JMS serializer exists use the [JMS\Serializer\ArrayTransformerInterface]
     * since the library renamed the NormalizerInterface to the above name. In order to support both versions the
     * concrete Serializer class will be used
     *
     * JMSDenormalizerAdapter constructor.
     * @param Serializer $denormalizer
     */
    public function __construct(Serializer $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * Denormalizes data back into an object of the given class.
     *
     * @param mixed $data data to restore
     * @param string $class the expected class to instantiate
     * @param string $format format the given data was extracted from
     * @param array $context options available to the denormalizer
     *
     * @return object
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        return $this->denormalizer->fromArray($data,$class,$this->mergeContext($context,DeserializationContext::create()));
    }

    /**
     * Checks whether the given class is supported for denormalization by this normalizer.
     *
     * @param mixed $data Data to denormalize from
     * @param string $type The class to which the data should be denormalized
     * @param string $format The format being deserialized from
     *
     * @return bool
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return true;
    }
}