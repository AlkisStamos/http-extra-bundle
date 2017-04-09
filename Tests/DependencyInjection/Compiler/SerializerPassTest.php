<?php

namespace Alks\HttpExtraBundle\Tests\DependencyInjection\Compiler;
use Alks\HttpExtraBundle\DependencyInjection\Compiler\SerializerPass;
use Alks\HttpExtraBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class SerializerPassTest
 * @package Alks\HttpExtraBundle\Tests\DependencyInjection\Compiler
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class SerializerPassTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
    }

    public function testPassWithDeclaredSerializer()
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $this->container->register('alks_http.serializer',get_class($serializer));
        $pass = new SerializerPass();
        $pass->process($this->container);
        $this->assertSame(get_class($serializer),$this->container->getDefinition('alks_http.serializer')->getClass());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassWithUnsupportedSerializerDefinition()
    {
        $serializer = $this->createMock(UnsupportedSerializer::class);
        $this->container->register('alks_http.serializer',get_class($serializer));
        $pass = new SerializerPass();
        $pass->process($this->container);
    }
    
    public function testPassWithJMSSerializer()
    {
        if(!interface_exists('\JMS\Serializer\SerializerInterface'))
        {
            $this->markTestSkipped(
                'JMS serializer not loaded'
            );
        }
        $serializer = $this->createMock(\JMS\Serializer\SerializerInterface::class);
        $this->container->register('jms_serializer.serializer',get_class($serializer));
        $pass = new SerializerPass();
        $pass->process($this->container);
        $this->assertSame('alks_http.jms_serializer',(string)$this->container->getAlias('alks_http.serializer'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassWithUnsupportedJMSSerializer()
    {
        if(!interface_exists('\JMS\Serializer\SerializerInterface'))
        {
            $this->markTestSkipped(
                'JMS serializer not loaded'
            );
        }
        $serializer = $this->createMock(UnsupportedSerializer::class);
        $this->container->register('jms_serializer.serializer',get_class($serializer));
        $pass = new SerializerPass();
        $pass->process($this->container);
    }

    public function testPassWithSymfonySerializer()
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $this->container->register('serializer',get_class($serializer));
        $pass = new SerializerPass();
        $pass->process($this->container);
        $this->assertSame('serializer',(string)$this->container->getAlias('alks_http.serializer'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassWithUnsupportedCoreSerializer()
    {
        $serializer = $this->createMock(UnsupportedSerializer::class);
        $this->container->register('serializer',get_class($serializer));
        $pass = new SerializerPass();
        $pass->process($this->container);
    }

    public function testPassWithNoSerializer()
    {
        $pass = new SerializerPass();
        $pass->process($this->container);
        $this->assertFalse($this->container->has('alks_http.serializer'));
    }
}

class UnsupportedSerializer {}