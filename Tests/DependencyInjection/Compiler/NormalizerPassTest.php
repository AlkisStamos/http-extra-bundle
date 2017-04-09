<?php

namespace Alks\HttpExtraBundle\Tests\DependencyInjection\Compiler;
use Alks\HttpExtraBundle\DependencyInjection\Compiler\NormalizerPass;
use Alks\HttpExtraBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Class NormalizerPassTest
 * @package Alks\HttpExtraBundle\Tests\DependencyInjection\Compiler
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class NormalizerPassTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
    }

    public function testPassWithDeclaredDenormalizer()
    {
        if(!interface_exists('Symfony\Component\Serializer\Normalizer\DenormalizerInterface'))
        {
            $this->markTestSkipped(
                'JMS serializer/denormalizer not loaded'
            );
        }
        $normalizer = $this->createMock(DenormalizerInterface::class);
        $this->container->register('alks_http.denormalizer',get_class($normalizer));
        $pass = new NormalizerPass();
        $pass->process($this->container);
        $this->assertSame(get_class($normalizer),$this->container->getDefinition('alks_http.denormalizer')->getClass());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassWithUnsupportedDenormalizerDefinition()
    {
        $normalizer = $this->createMock(UnsupportedDenormalizer::class);
        $this->container->register('alks_http.denormalizer',get_class($normalizer));
        $pass = new NormalizerPass();
        $pass->process($this->container);
    }

    public function testPassWithJMSSerializer()
    {
        if(!class_exists('\JMS\Serializer\Serializer'))
        {
            $this->markTestSkipped(
                'JMS serializer/denormalizer not loaded'
            );
        }
        $denormalizer = $this->createMock(\JMS\Serializer\Serializer::class);
        $this->container->register('jms_serializer.serializer',get_class($denormalizer));
        $pass = new NormalizerPass();
        $pass->process($this->container);
        $this->assertSame('alks_http.jms_denormalizer',(string)$this->container->getAlias('alks_http.denormalizer'));
    }

    public function testPassWithEmptyDenormalizer()
    {
        $pass = new NormalizerPass();
        $pass->process($this->container);
        $this->assertFalse($this->container->has('alks_http.denormalizer'));
    }

    public function testPassWithUnsupportedJMSSerializer()
    {
        $denormalizer = $this->createMock(UnsupportedDenormalizer::class);
        $this->container->register('jms_serializer.serializer',get_class($denormalizer));
        $pass = new NormalizerPass();
        $pass->process($this->container);
        $this->assertFalse($this->container->has('alks_http.denormalizer'));
    }

    public function testPassWithSymfonySerializer()
    {
        $serializer = $this->createMock(Serializer::class);
        $this->container->register('serializer',get_class($serializer));
        $pass = new NormalizerPass();
        $pass->process($this->container);
        $this->assertSame('serializer',(string)$this->container->getAlias('alks_http.denormalizer'));
    }
}

class UnsupportedDenormalizer {}