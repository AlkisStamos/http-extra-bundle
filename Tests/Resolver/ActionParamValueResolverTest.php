<?php

namespace Alks\HttpExtraBundle\Tests\Resolver;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Alks\HttpExtraBundle\Annotation\ActionParam;
use Alks\HttpExtraBundle\Annotation\RequestBody;
use Alks\HttpExtraBundle\Annotation\RequestData;
use Alks\HttpExtraBundle\Annotation\RequestParam;
use Alks\HttpExtraBundle\EventListener\ActionListener;
use Alks\HttpExtraBundle\Negotiation\NegotiationResult;
use Alks\HttpExtraBundle\Resolver\ActionParamValueResolver;
use Alks\HttpExtraBundle\Resolver\ConfigurationResolver;
use Alks\HttpExtraBundle\Tests\TestCase;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ActionParamValueResolverTest
 * @package Alks\HttpExtraBundle\Tests\Resolver
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class ActionParamValueResolverTest extends TestCase
{
    /**
     * @param ActionParam[] $actionParameters
     * @return ActionListener
     */
    private function getActionListener(array $actionParameters)
    {
        $mock = $this->createMock(ActionListener::class);
        $mock->expects($this->any())
            ->method('getActionParameters')
            ->willReturn($actionParameters);
        return $mock;
    }

    private function getValueResolver($listener=null, $configuration=null)
    {
        $configuration = $configuration === null ? $this->createMock(ConfigurationResolver::class) : $configuration;
        $listener = $listener === null ? $this->getActionListener([]) : $listener;
        return new ActionParamValueResolver(
            $configuration,$listener
        );
    }

    private function getArgumentMetadata($name, $type=null, $isVariadic=false, $hasDefaultValue=false, $defaultValue=null, $isNullable=false)
    {
        return new ArgumentMetadata($name,$type,$isVariadic,$hasDefaultValue,$defaultValue,$isNullable);
    }

    public function testSupportsWithEmptyParameters()
    {
        $resolver = $this->getValueResolver(
            $this->getActionListener([])
        );
        $argument = $this->getArgumentMetadata('foo');
        $request = Request::create('/');
        $this->assertFalse($resolver->supports($request,$argument));
    }

    public function testSupportsWithValidParameter()
    {
        $listener = $this->getActionListener([
            'foo' => new ActionParam(['bindTo'=>'foo'])
        ]);
        $resolver = $this->getValueResolver($listener);
        $argument = $this->getArgumentMetadata('foo');
        $this->assertTrue($resolver->supports(Request::create('/'),$argument));
    }

    public function testSupportsWithBoundRoute()
    {
        $param = new ActionParam(['bindTo'=>'foo','route'=>'bar']);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $request = Request::create('/');
        $request->attributes->set('_route','bar');
        $resolver = $this->getValueResolver($listener);
        $argument = $this->getArgumentMetadata('foo');
        $this->assertTrue($resolver->supports($request,$argument));
    }

    public function testNotSupportsWithBoundRoute()
    {
        $param = new ActionParam(['bindTo'=>'foo','route'=>'foo']);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $request = Request::create('/');
        $request->attributes->set('_route','bar');
        $resolver = $this->getValueResolver($listener);
        $argument = $this->getArgumentMetadata('foo');
        $this->assertFalse($resolver->supports($request,$argument));
    }

    public function testSupportWithInvalidParameterType()
    {
        $listener = $this->getActionListener([
            'foo' => null
        ]);
        $request = Request::create('/');
        $resolver = $this->getValueResolver($listener);
        $argument = $this->getArgumentMetadata('foo');
        $this->assertFalse($resolver->supports($request,$argument));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testResolveWithInvalidContent()
    {
        $listener = $this->getActionListener([
            'foo' => null
        ]);
        $request = Request::create('/');
        $resolver = $this->getValueResolver($listener);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item) {}
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testResolveWithInvalidRequestContent()
    {
        $param = new ActionParam(['bindTo'=>'foo','route'=>'foo']);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $request = Request::create('/');
        $resolver = $this->getValueResolver($listener);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item) {}
    }

    /** Request Data Resolve */

    public function testResolveRequestData()
    {
        $param = new RequestData(['bindTo'=>'foo','name'=>'foo']);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $request = Request::create('/');
        $request->request->set('foo','bar');
        $resolver = $this->getValueResolver($listener);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame('bar',$item);
        }
    }

    public function testResolveRequestDataDefaultValue()
    {
        $param = new RequestData(['bindTo'=>'foo','name'=>'foo','defaultValue'=>'bar']);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $request = Request::create('/');
        $resolver = $this->getValueResolver($listener);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame('bar',$item);
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testUnresolvedRequestData()
    {
        $param = new RequestData(['bindTo'=>'foo','name'=>'foo']);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $request = Request::create('/');
        $resolver = $this->getValueResolver($listener);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item){}
    }

    public function testResolveRequestDataAll()
    {
        $param = new RequestData(['bindTo'=>'foo']);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $request = Request::create('/');
        $request->request->set('bar','baz');
        $resolver = $this->getValueResolver($listener);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame(['bar'=>'baz'],$item);
        }
    }

    public function testResolveRequestDataAllWithEmptyRequest()
    {
        $param = new RequestData(['bindTo'=>'foo','defaultValue'=>'bar']);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $request = Request::create('/');
        $resolver = $this->getValueResolver($listener);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame('bar',$item);
        }
    }

    public function testResolveRequestDataAllWithCustomType()
    {
        $param = new RequestData(['bindTo'=>'foo','defaultValue'=>'bar','type'=>ACustomType::class]);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $request = Request::create('/');
        $resolver = $this->getValueResolver($listener);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame('bar',$item);
        }
    }

    public function testResolveRequestDataAllWithUnknownType()
    {
        $param = new RequestData(['bindTo'=>'foo','defaultValue'=>'bar','type'=>'UnknownType']);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $request = Request::create('/');
        $request->request->set('foo','bar');
        $resolver = $this->getValueResolver($listener);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame(['foo'=>'bar'],$item);
        }
    }

    public function testResolveRequestDataAllWithDenormalizer($return=null, $denormalizer=null, $returnType=null, RequestData $param=null)
    {
        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->once())
            ->method('isNormalizerEnabled')
            ->willReturn(true);

        $data = ['foo'=>'bar'];
        $request = Request::create('/');
        $request->request->set('foo','bar');
        $customType = $returnType === null ? ACustomType::class : $returnType;
        $return = $return === null ? 'bar' : $return;

        if($denormalizer === null)
        {
            $denormalizer = $this->createMock(DenormalizerInterface::class);
            $denormalizer->expects($this->once())
                ->method('supportsDenormalization')
                ->with($request->request,$customType)
                ->willReturn(true);
            $denormalizer->expects($this->once())
                ->method('denormalize')
                ->with($data,$customType)
                ->willReturn($return);
        }

        $param = $param === null ? new RequestData(['bindTo'=>'foo','type'=>$customType]) : $param;
        $listener = $this->getActionListener([
            'foo' => $param
        ]);

        $resolver = $this->getValueResolver($listener,$configuration);
        $resolver->setDenormalizer($denormalizer);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame('bar',$item);
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testResolveRequestDataAllEmptyDenormalization()
    {
        $this->testResolveRequestDataAllWithDenormalizer('');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRequestDataWithDenormalizerException()
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->once())
            ->method('supportsDenormalization')
            ->willReturn(true);
        $denormalizer->expects($this->once())
            ->method('denormalize')
            ->willThrowException(new \BadMethodCallException());
        $this->testResolveRequestDataAllWithDenormalizer(null,$denormalizer);
    }

    public function testRequestDataDenormalizerWithNotSupportedRequest()
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->once())
            ->method('supportsDenormalization')
            ->willReturn(false);
        $requestData = new RequestData([
            'bindTo'=>'foo','type'=>ACustomType::class,'defaultValue'=>'default'
        ]);
        $request = Request::create('/');
        $request->request->set('foo','bar');
        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->once())
            ->method('isNormalizerEnabled')
            ->willReturn(true);
        $listener = $this->getActionListener([
            'foo' => $requestData
        ]);
        $resolver = $this->getValueResolver($listener,$configuration);
        $resolver->setDenormalizer($denormalizer);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame('default', $item);
        }
    }

    public function testRequestDataWithoutDenormalizer()
    {
        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->atMost(0))
            ->method('isNormalizerEnabled')
            ->willReturn(true);

        $request = Request::create('/');
        $request->request->set('foo','bar');
        $param = new RequestData(['bindTo'=>'foo','type'=>ACustomType::class,'defaultValue'=>'bar']);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $resolver = $this->getValueResolver($listener,$configuration);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame('bar',$item);
        }
    }

    /** Request Body Resolve */

    public function testResolveRequestBody()
    {
        $param = new RequestBody(['bindTo'=>'foo']);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $request = $this->getRequestMock([],[],'request_body');

        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->once())
            ->method('resolveContentType')
            ->with($request)
            ->willReturn(new NegotiationResult('foo','bar'));

        $resolver = $this->getValueResolver($listener,$configuration);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame('request_body',$item);
        }
    }
    
    public function testResolveRequestBodyWithCustomType($configuration=null, $request=null, $expected=null, RequestBody $param=null, SerializerInterface $serializer=null)
    {
        if($param === null)
        {
            $param = new RequestBody([
                'bindTo' => 'foo',
                'format' => 'bar'
            ]);
        }
        if($configuration === null)
        {
            $configuration = $this->createMock(ConfigurationResolver::class);
            $configuration->expects($this->once())
                ->method('getTypeFromKey')
                ->with($param->getFormat())
                ->willReturn(new NegotiationResult('foo','bar'));
        }
        $request = $request === null ? $this->getRequestMock([],[],'request_body') : $request;
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $expected = $expected === null ? 'request_body' : $expected;
        $resolver = $this->getValueResolver($listener,$configuration);
        if($serializer !== null)
        {
            $resolver->setSerializer($serializer);
        }
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame($expected,$item);
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testResolveRequestBodyWithUnknownType()
    {
        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->once())
            ->method('getTypeFromKey')
            ->willReturn(null);
        $this->testResolveRequestBodyWithCustomType($configuration);
    }

    public function testResolveRequestBodyWithEmptyContent()
    {
        $request = $this->getRequestMock();
        $param = new RequestBody([
            'bindTo' => 'foo',
            'format' => 'bar',
            'defaultValue' => 'baz'
        ]);
        $this->testResolveRequestBodyWithCustomType(null,$request,'baz',$param);
    }

    public function testResolveRequestBodyWithDisabledSerializer()
    {
        $configuration = $this->createMock(ConfigurationResolver::class);
        $param = new RequestBody([
            'bindTo' => 'foo',
            'format' => 'bar',
            'defaultValue' => 'baz',
            'type' => ACustomType::class
        ]);
        $configuration->expects($this->once())
            ->method('getTypeFromKey')
            ->willReturn(new NegotiationResult('foo','bar'));
        $configuration->expects($this->once())
            ->method('isSerializerEnabled')
            ->willReturn(false);
        $serializer = $this->createMock(SerializerInterface::class);
        $this->testResolveRequestBodyWithCustomType($configuration,null,null,$param,$serializer);
    }

    public function testResolveRequestBodyWithSerializer(SerializerInterface $serializer=null, $expectation=null)
    {
        $request = $this->getRequestMock([],[],'serialized_content');
        $param = new RequestBody([
            'bindTo' => 'foo',
            'format' => 'bar',
            'defaultValue' => 'baz',
            'type' => ACustomType::class
        ]);

        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->once())
            ->method('getTypeFromKey')
            ->with($param->getFormat())
            ->willReturn(new NegotiationResult('foo','bar'));

        $configuration->expects($this->once())
            ->method('isSerializerEnabled')
            ->willReturn(true);

        if($serializer === null)
        {
            $serializer = $this->createMock(SerializerInterface::class);
            $serializer->expects($this->once())
                ->method('deserialize')
                ->with('serialized_content',ACustomType::class,'foo')
                ->willReturn('deserialized_content');
        }
        $expectation = $expectation === null ? 'deserialized_content' : $expectation;
        $this->testResolveRequestBodyWithCustomType($configuration,$request,$expectation,$param,$serializer);
    }

    public function testResolveRequestBodyWithSerializerException()
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->willThrowException(new \Exception());
        $this->testResolveRequestBodyWithSerializer($serializer,'baz');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testResolveRequestBodyWithSerializerHttpException()
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->willThrowException(new HttpException('0'));
        $this->testResolveRequestBodyWithSerializer($serializer);
    }

    public function testResolveRequestBodyWithEmptyDeserializationResult()
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn(null);
        $this->testResolveRequestBodyWithSerializer($serializer,'baz');
    }

    /** Validator tests */

    public function testResolveRequestBodyWithValidator(ValidatorInterface $validator=null)
    {
        if(!interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface'))
        {
            $this->markTestSkipped(
                'Symfony validator interface not loaded, skipping validation tests'
            );
        }
        $parameterType = ACustomType::class;
        $deserializedContent = new ACustomType();
        $serializedContent = 'request_body';
        $contentFormat = 'foo';

        if($validator === null)
        {
            $validator = $this->createMock(ValidatorInterface::class);
            $validator->expects($this->once())
                ->method('validate')
                ->with($deserializedContent)
                ->willReturn([]);
        }

        $param = new RequestBody([
            'bindTo' => 'foo',
            'format' => $contentFormat,
            'type' => $parameterType,
            'validate' => true
        ]);

        $request = $this->getRequestMock([],[],$serializedContent);

        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->once())
            ->method('isSerializerEnabled')
            ->willReturn(true);
        $configuration->expects($this->once())
            ->method('isValidatorEnabled')
            ->willReturn(true);
        $configuration->expects($this->once())
            ->method('getTypeFromKey')
            ->willReturn(new NegotiationResult('foo','bar'));

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->with($serializedContent,$parameterType,$contentFormat)
            ->willReturn($deserializedContent);

        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $resolver = $this->getValueResolver($listener,$configuration);
        $resolver->setSerializer($serializer);
        $resolver->setValidator($validator);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame($deserializedContent,$item);
        }
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testResolverRequestBodyWithValidationErrors()
    {
        if(!interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface'))
        {
            $this->markTestSkipped(
                'Symfony validator interface not loaded, skipping validation tests'
            );
        }
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->expects($this->once())
            ->method('getParameters')
            ->willReturn(['parameter']);
        $violation->expects($this->once())
            ->method('getMessage')
            ->willReturn('violation message');

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->willReturn([
                $violation
            ]);
        $this->testResolveRequestBodyWithValidator($validator);
    }

    /** Request Param Resolve */

    public function testResolveRequestParam()
    {
        $request = Request::create('/','GET',['foo'=>'bar']);
        $param = new RequestParam([
            'bindTo' => 'foo'
        ]);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $resolver = $this->getValueResolver($listener);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame('bar',$item);
        }
    }

    public function testResolveRequestParamWithDefaultValue()
    {
        $request = Request::create('/');
        $param = new RequestParam([
            'bindTo' => 'foo',
            'defaultValue' => 'baz'
        ]);
        $listener = $this->getActionListener([
            'foo' => $param
        ]);
        $resolver = $this->getValueResolver($listener);
        $result = $resolver->resolve($request,$this->getArgumentMetadata('foo'));
        foreach($result as $item)
        {
            $this->assertSame('baz',$item);
        }
    }

    public function testResolveRequestParamWithDoctrineRepository(RequestParam $param=null, ObjectRepository $repository=null, $doctrine=null, ArgumentMetadata $argumentMetadata=null)
    {
        if(!interface_exists('Symfony\Bridge\Doctrine\RegistryInterface'))
        {
            $this->markTestSkipped(
                'Doctrine not loaded'
            );
        }
        if($param === null)
        {
            $param = new RequestParam([
                'bindTo' => 'foo',
                'findBy' => 'id',
                'type' => ACustomType::class
            ]);
        }
        $request = Request::create('/','GET',['foo'=>'bar']);

        if($repository === null)
        {
            $repository = $this->createMock(ObjectRepository::class);
            $repository->expects($this->once())
                ->method('findOneBy')
                ->with(['id'=>'bar'])
                ->willReturn('baz');
        }

        if($doctrine === null)
        {
            $doctrine = $this->createMock(RegistryInterface::class);
            $doctrine->expects($this->once())
                ->method('getRepository')
                ->with(ACustomType::class)
                ->willReturn($repository);
        }

        $listener = $this->getActionListener([
            'foo' => $param
        ]);

        $resolver = $this->getValueResolver($listener);
        if($doctrine instanceof RegistryInterface)
        {
            $resolver->setDoctrineRegistry($doctrine);
        }
        if($argumentMetadata === null)
        {
            $argumentMetadata = $this->getArgumentMetadata('foo');
        }
        $result = $resolver->resolve($request,$argumentMetadata);
        foreach($result as $item)
        {
            $this->assertSame('baz',$item);
        }
    }

    public function testResolveRequestParamFindByWithoutRegistry()
    {
        if(!interface_exists('Symfony\Bridge\Doctrine\RegistryInterface'))
        {
            $this->markTestSkipped(
                'Doctrine not loaded'
            );
        }
        $param = new RequestParam([
            'bindTo' => 'foo',
            'findBy' => 'id',
            'type' => ACustomType::class,
            'defaultValue' => 'baz'
        ]);
        $repository = $this->createMock(ObjectRepository::class);
        $this->testResolveRequestParamWithDoctrineRepository($param,$repository,false);
    }

    public function testResolveRequestParamWithDoctrineManager()
    {
        if(!interface_exists('Symfony\Bridge\Doctrine\RegistryInterface'))
        {
            $this->markTestSkipped(
                'Doctrine not loaded'
            );
        }
        $param = new RequestParam([
            'bindTo' => 'foo',
            'findBy' => 'name',
            'type' => ACustomType::class,
            'manager' => 'DoctrineManager'
        ]);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name'=>'bar'])
            ->willReturn('baz');

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(ACustomType::class)
            ->willReturn($repository);

        $doctrine = $this->createMock(RegistryInterface::class);
        $doctrine->expects($this->once())
            ->method('getManager')
            ->with('DoctrineManager')
            ->willReturn($manager);

        $this->testResolveRequestParamWithDoctrineRepository($param,$repository,$doctrine);
    }

    public function testResolveRequestParamWithDoctrineArrayType()
    {
        if(!interface_exists('Symfony\Bridge\Doctrine\RegistryInterface'))
        {
            $this->markTestSkipped(
                'Doctrine not loaded'
            );
        }
        $metadata = $this->getArgumentMetadata('foo','array');
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['id'=>'bar'])
            ->willReturn('baz');
        $this->testResolveRequestParamWithDoctrineRepository(null,$repository,null,$metadata);
    }

    public function testResolveRequestParamWithDoctrineException()
    {
        if(!interface_exists('Symfony\Bridge\Doctrine\RegistryInterface'))
        {
            $this->markTestSkipped(
                'Doctrine not loaded'
            );
        }
        $param = new RequestParam([
            'bindTo' => 'foo',
            'findBy' => 'name',
            'defaultValue' => 'baz'
        ]);
        $metadata = $this->getArgumentMetadata('foo',ACustomType::class);
        $repository = $this->createMock(ObjectRepository::class);
        $doctrine = $this->createMock(RegistryInterface::class);
        $doctrine->expects($this->once())
            ->method('getRepository')
            ->willThrowException(new \Exception());
        $this->testResolveRequestParamWithDoctrineRepository($param,$repository,$doctrine,$metadata);
    }

    public function testResolveRequestParamWithDoctrineUnknownType()
    {
        if(!interface_exists('Symfony\Bridge\Doctrine\RegistryInterface'))
        {
            $this->markTestSkipped(
                'Doctrine not loaded'
            );
        }
        $param = new RequestParam([
            'bindTo' => 'foo',
            'findBy' => 'name'
        ]);
        $metadata = $this->getArgumentMetadata('foo','UnknownType',false,true,'baz');
        $repository = $this->createMock(ObjectRepository::class);
        $doctrine = $this->createMock(RegistryInterface::class);
        $this->testResolveRequestParamWithDoctrineRepository($param,$repository,$doctrine,$metadata);
    }
}

class ACustomType {}