<?php

namespace Alks\HttpExtraBundle\Tests\Serializer;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerBuilder;
use Alks\HttpExtraBundle\Serializer\JMSSerializerAdapter;
use Alks\HttpExtraBundle\Tests\TestCase;

/**
 * Class JMSSerializerAdapterTest
 * @package Alks\HttpExtraBundle\Tests\Serializer
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class JMSSerializerAdapterTest extends TestCase
{
    private $_serializer;

    protected function setUp()
    {
        parent::setUp();
        if(!interface_exists('\JMS\Serializer\SerializerInterface'))
        {
            $this->markTestSkipped(
                'JMS serializer not loaded'
            );
        }
    }

    private function getSerializer($newInstance=false)
    {
        if($newInstance)
        {
            $this->_serializer = null;
        }
        if($this->_serializer === null)
        {
            $this->_serializer = new JMSSerializerAdapter(
                SerializerBuilder::create()->build()
            );
        }
        return $this->_serializer;
    }

    public function testSerializeWithoutContext()
    {
        $expect = '{"foo":1,"bar":2}';
        $this->assertSame($expect,$this->getSerializer()->serialize([
            'foo'=>1, 'bar'=>2
        ],'json'));
    }

    public function testDeserializerWithoutContext()
    {
        $this->assertSame(['foo'=>1, 'bar'=>2],$this->getSerializer()->deserialize('{"foo":1,"bar":2}','array','json'));
    }

    public function testDeserializiationContextMerge()
    {
        $context = DeserializationContext::create();
        $reflection = $this->getAccessMethodReflection(JMSSerializerAdapter::class,'mergeContext');
        if($reflection === null)
        {
            $this->markTestSkipped('Cannot get valid access to context merge method');
        }
        $groups = ['foo','bar','baz'];
        $merged = $reflection->invokeArgs($this->getSerializer(),[
            [
                'groups' => $groups,
                'version' => 4,
                'enable_max_depth' => true,
                'maxDepth' => 3,
                'foo' => 'bar'
            ],
            $context
        ]);
        $this->assertSame($context,$merged);
        $this->assertSame(3,$context->getDepth());
        $this->assertTrue($context->attributes->containsKey('groups'));
        $this->assertTrue($context->attributes->containsKey('version'));
        $this->assertSame(4,$context->attributes->get('version')->get());
        $this->assertEquals($groups,$context->attributes->get('groups')->get());
        $this->assertTrue($context->attributes->containsKey('foo'));
        $this->assertSame('bar',$context->attributes->get('foo')->get());
    }
}