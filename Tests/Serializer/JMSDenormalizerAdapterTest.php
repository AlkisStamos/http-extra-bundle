<?php

namespace Alks\HttpExtraBundle\Tests\Serializer;
use JMS\Serializer\Serializer;
use Alks\HttpExtraBundle\Serializer\JMSDenormalizerAdapter;
use Alks\HttpExtraBundle\Tests\TestCase;

/**
 * Class JMSDenormalizerAdapterTest
 * @package Alks\HttpExtraBundle\Tests\Serializer
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class JMSDenormalizerAdapterTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        if(!class_exists('\JMS\Serializer\Serializer'))
        {
            $this->markTestSkipped(
                'JMS serializer/denormalizer not loaded'
            );
        }
    }

    public function testDenormalize()
    {
        $data = [
            'foo' => 'bar'
        ];
        $serializerStub = $this->createMock(Serializer::class);
        $serializerStub->expects($this->once())
            ->method('fromArray')
            ->with($data);
        $denormalizer = new JMSDenormalizerAdapter($serializerStub);
        $denormalizer->denormalize($data,'ANY');
    }

    public function testSupportsDenormalization()
    {
        $serializerStub = $this->createMock(Serializer::class);
        $denormalizer = new JMSDenormalizerAdapter($serializerStub);
        $this->assertTrue($denormalizer->supportsDenormalization(null,null));
    }
}

class ObjectTest
{
    protected $foo;
    protected $bar;

    /**
     * ObjectTest constructor.
     * @param $foo
     * @param $bar
     */
    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}