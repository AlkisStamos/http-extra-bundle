<?php

namespace Alks\HttpExtraBundle\Tests;

use JMS\Serializer\Serializer;
use Alks\HttpExtraBundle\Negotiation\NegotiationInterface;
use Alks\HttpExtraBundle\Resolver\ConfigurationResolver;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TestCase
 * @package Alks\HttpExtraBundle\Tests
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function getTypes()
    {
        return [
            [
                'name' => 'json',
                'values'=> [
                    'application/json','text/json'
                ],
                'restrict' => null
            ],
            [
                'name' => 'xml',
                'values' => [
                    'application/xml','text/xml'
                ],
                'restrict' => null
            ]
        ];
    }

    protected function getHeaders()
    {
        return [
            'content_type' => 'content-type',
            'accept_type' => 'accept'
        ];
    }

    protected function getPrioritiesFromValues($values)
    {
        $priorities = [];
        foreach($values as $item)
        {
            $priorities = array_merge($priorities,$item['values']);
        }
        return $priorities;
    }

    protected static function getAccessMethodReflection($class, $method)
    {
        if(class_exists($class))
        {
            $class = new \ReflectionClass($class);
            if($class->hasMethod($method))
            {
                $method = $class->getMethod($method);
                $method->setAccessible(true);
                return $method;
            }
        }
        return null;
    }
    
    protected function getRequestMock(array $headers=[],array $query=[],$body='',array $data=[])
    {
        
        $stub = $this->createMock(Request::class);
        $stub->expects($this->any())
            ->method('getContent')->willReturn($body);
        /** @var Request $stub */
        $stub->headers = $this->createMock(ParameterBag::class);
        $stub->headers->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($headers));
        $stub->query = $this->createMock(ParameterBag::class);
        $stub->query->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($query));
        $stub->request = $this->createMock(ParameterBag::class);
        $stub->request->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($data));
        return $stub;
    }

    protected function getNegotiatorMock($returnType='application/json', $returnLanguage='en-US')
    {
        $stub = $this->createMock(NegotiationInterface::class);
        $stub->expects($this->any())
            ->method('negotiateType')
            ->willReturn($returnType);
        return $stub;
    }
    
    protected function getConfigurationResolverMock()
    {
        $stub = $this->createMock(ConfigurationResolver::class);
        $stub->expects($this->any())
            ->method('load');
        return $stub;
    }

    protected function getJMSSerializerMock($serializedReturn,$deserializedReturn)
    {
        $stub = $this->createMock('\JMS\Serializer\SerializerInterface');
        $stub->expects($this->any())
            ->method('serialize')
            ->willReturn($serializedReturn);
        $stub->expects($this->any())
            ->method('deserialize')
            ->willReturn($deserializedReturn);
        return $stub;
    }

    protected function getDoctrineRegistryMock()
    {
        $stub = $this->createMock(RegistryInterface::class);
        return $stub;
    }
}