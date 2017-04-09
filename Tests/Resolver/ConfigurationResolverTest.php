<?php

namespace Alks\HttpExtraBundle\Tests\Resolver;

use Alks\HttpExtraBundle\Negotiation\NegotiationInterface;
use Alks\HttpExtraBundle\Negotiation\NegotiationResult;
use Alks\HttpExtraBundle\Resolver\ConfigurationResolver;
use Alks\HttpExtraBundle\Tests\Negotiation\NegotiatorTest;
use Alks\HttpExtraBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConfigurationResolverTest
 * @package Alks\HttpExtraBundle\Tests\Resolver
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class ConfigurationResolverTest extends TestCase
{
    private $_resolver;

    private function getResolver(NegotiationInterface $negotiator=null, $newInstance=false)
    {
        if($newInstance)
        {
            $this->_resolver = null;
        }
        if($this->_resolver === null)
        {
            $this->_resolver = new ConfigurationResolver(
                $negotiator === null ? $this->getNegotiatorMock() : $negotiator
            );
        }
        return $this->_resolver;
    }

    public function testInitializeWithoutLoad()
    {
        $this->assertSame('json',$this->getResolver()->getTypeFromKey('json')->getName());
        $this->assertNull($this->getResolver()->getTypeFromKey('html'));
    }

    public function testOverrideTypes()
    {
        $this->getResolver()->load([
            'types' => [
                [
                    'name' => 'html',
                    'values' => ['text/html']
                ]
            ]
        ]);
        $this->assertSame('html',$this->getResolver()->getTypeFromKey('html')->getName());
        $this->assertNull($this->getResolver()->getTypeFromKey('json'));
        $this->assertNull($this->getResolver()->getTypeFromKey('xml'));
    }

    public function testAppendTypes()
    {
        $this->getResolver()->load([
            'append_types' => [
                [
                    'name' => 'html',
                    'values' => ['text/html']
                ]
            ]
        ]);
        $this->assertSame('html',$this->getResolver()->getTypeFromKey('html')->getName());
        $this->assertSame('json',$this->getResolver()->getTypeFromKey('json')->getName());
        $this->assertSame('xml',$this->getResolver()->getTypeFromKey('xml')->getName());
    }

    public function testOverrideNonNegotiationOption()
    {
        $this->getResolver()->load([
            'headers' => [
                'content_type' => 'foo'
            ]
        ]);
        $this->assertSame('json',$this->getResolver()->getTypeFromKey('json')->getName());
        $this->assertSame('xml',$this->getResolver()->getTypeFromKey('xml')->getName());
    }

    public function testOverrideSpecificType()
    {
        $this->getResolver()->load([
            'append_types' => [
                [
                    'name' => 'json',
                    'values' => ['foo/bar','bar/baz']
                ]
            ]
        ]);
        $this->assertSame('json',$this->getResolver()->getTypeFromKey('json')->getName());
        $this->assertSame('foo/bar',$this->getResolver()->getTypeFromKey('json')->getValue());
        $this->assertSame('bar/baz',$this->getResolver()->getTypeFromKey('json',1)->getValue());
    }

    public function testTypeResolveWithoutNegotiation()
    {
        $this->assertSame('json',$this->getResolver()->getAcceptType(
            $this->getRequestMock([
                'accept' => 'text/xml'
            ])
        ));
    }

    public function testResolveAcceptTypeWithNegotiation()
    {
        $requestStub = $this->getRequestMock([
            'accept' => 'application/xml',
            'content-type' => 'application/xml'
        ]);
        $resolver = $this->getResolver($this->getNegotiatorMock('application/xml'))->load([
            'negotiation' => [
                'enabled' => true
            ]
        ]);
        $acceptType = $resolver->resolveAcceptType($requestStub);
        $this->assertInstanceOf(NegotiationResult::class,$acceptType);
        $rerun = $resolver->resolveAcceptType($requestStub);
        $this->assertSame($acceptType,$rerun);
        $this->assertSame('xml',$acceptType->getName());
    }

    public function testResolveContentTypeWithNegotiation()
    {
        $requestStub = $this->getRequestMock([
            'accept' => 'application/xml',
            'content-type' => 'application/xml'
        ]);
        $resolver = $this->getResolver()->load([
            'negotiation' => [
                'enabled' => true
            ]
        ]);
        $contentType = $resolver->resolveContentType($requestStub);
        $this->assertInstanceOf(NegotiationResult::class,$contentType);
        $rerun = $resolver->resolveContentType($requestStub);
        $this->assertSame($contentType,$rerun);
        $this->assertSame('json',$contentType->getName());
    }

    public function testResolveUnresolvedNegotiatedContentType()
    {
        $requestStub = $this->getRequestMock([
            'accept' => 'foo/bar',
            'content-type' => 'foo/bar'
        ]);
        $resolver = $this->getResolver($this->getNegotiatorMock('foo/bar'))->load([
            'negotiation' => [
                'enabled' => true
            ]
        ]);
        $this->assertSame('json',$resolver->getContentType($requestStub));
    }

    public function testResolveUnresolvedNegotiatedAcceptType()
    {
        $requestStub = $this->getRequestMock([
            'accept' => 'foo/bar',
            'content-type' => 'foo/bar'
        ]);
        $resolver = $this->getResolver($this->getNegotiatorMock('foo/bar'))->load([
            'negotiation' => [
                'enabled' => true
            ]
        ]);
        $this->assertSame('json',$resolver->getAcceptType($requestStub));
    }

    public function testResolveTypeWithFailedNegotiation()
    {
        $requestStub = $this->getRequestMock([
            'accept' => 'foo/bar',
            'content-type' => 'foo/bar'
        ]);
        $resolver = $this->getResolver($this->getNegotiatorMock(null))->load([
            'negotiation' => [
                'enabled' => true
            ]
        ]);
        $this->assertSame('json',$resolver->getContentType($requestStub));
    }

    public function testDefaultConfigurationLoad()
    {
        $expect = [
            'type' => [],
            'headers' => $this->getHeaders()
        ];
        foreach($this->getTypes() as $type)
        {
            $expect['type'][$type['name']] = $type['values'];
        }
        $resolver = $this->getResolver();
        $this->assertEquals($expect,$resolver->getConfiguration());
    }
}