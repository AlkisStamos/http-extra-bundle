<?php

namespace Alks\HttpExtraBundle\Tests\EventListener;
use Doctrine\Common\Annotations\Reader;
use Alks\HttpExtraBundle\Annotation\ActionParam;
use Alks\HttpExtraBundle\Annotation\RequestParam;
use Alks\HttpExtraBundle\Annotation\RequestParams;
use Alks\HttpExtraBundle\Annotation\Response;
use Alks\HttpExtraBundle\Annotation\ResponseHeader;
use Alks\HttpExtraBundle\EventListener\ActionListener;
use Alks\HttpExtraBundle\Negotiation\NegotiationResult;
use Alks\HttpExtraBundle\Resolver\ConfigurationResolver;
use Alks\HttpExtraBundle\Tests\TestCase;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ActionListenerTest
 * @package Alks\HttpExtraBundle\Tests\EventListener
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class ActionListenerTest extends TestCase
{
    protected function getReaderStub(array $actionParams=[])
    {
        $readerStub = $this->createMock(Reader::class);
        $readerStub->expects($this->once())
            ->method('getMethodAnnotations')
            ->willReturn($actionParams);
        return $readerStub;
    }

    protected function getKernelControllerEventStub($controller)
    {
        $eventStub = $this->createMock(FilterControllerEvent::class);
        $eventStub->expects($this->any())
            ->method('getController')
            ->willReturn($controller);
        return $eventStub;
    }

    public function testKernelControllerBind()
    {
        $action = new ActionParam([
            'bindTo' => 'foo'
        ]);
        $params = new RequestParams([
            'value' => [
                'bar', 'baz'
            ]
        ]);
        $expect = [
            'foo' => $action,
            'bar' => new RequestParam(['value'=>'bar']),
            'baz' => new RequestParam(['value' => 'baz'])
        ];
        $reader = $this->getReaderStub([$action,$params]);
        $event = $this->getKernelControllerEventStub([ControllerForActionListenerTest::class,'action']);
        $configuration = $this->getConfigurationResolverMock();
        $listener = new ActionListener($reader,$configuration);
        $listener->onKernelController($event);
        $this->assertEquals($expect,$listener->getActionParameters());
    }

    public function testKernelControllerWithInvokeObject()
    {
        $action = new ActionParam([
            'bindTo' => 'foo'
        ]);
        $params = new RequestParams([
            'value' => [
                'bar', 'baz'
            ]
        ]);
        $expect = [
            'foo' => $action,
            'bar' => new RequestParam(['value'=>'bar']),
            'baz' => new RequestParam(['value' => 'baz'])
        ];
        $reader = $this->getReaderStub([$action,$params]);
        $event = $this->getKernelControllerEventStub(new ControllerForActionListenerTest());
        $configuration = $this->getConfigurationResolverMock();
        $listener = new ActionListener($reader,$configuration);
        $listener->onKernelController($event);
        $this->assertEquals($expect,$listener->getActionParameters());
    }

    public function testControllerWithReflectionMethod()
    {
        $action = new ActionParam([
            'bindTo' => 'foo'
        ]);
        $params = new RequestParams([
            'value' => [
                'bar', 'baz'
            ]
        ]);
        $expect = [
            'foo' => $action,
            'bar' => new RequestParam(['value'=>'bar']),
            'baz' => new RequestParam(['value' => 'baz'])
        ];
        $reader = $this->getReaderStub([$action,$params]);
        $method = new \ReflectionMethod(ControllerForActionListenerTest::class,'action');
        $event = $this->getKernelControllerEventStub($method);
        $configuration = $this->getConfigurationResolverMock();
        $listener = new ActionListener($reader,$configuration);
        $listener->onKernelController($event);
        $this->assertEquals($expect,$listener->getActionParameters());
    }

    public function testKernelControllerWithInvalidController()
    {
        $reader = $this->createMock(Reader::class);
        $closure = function(){};
        $event = $this->getKernelControllerEventStub($closure);
        $configuration = $this->getConfigurationResolverMock();
        $listener = new ActionListener($reader,$configuration);
        $listener->onKernelController($event);
        $this->assertEquals([],$listener->getActionParameters());
    }

    public function testKernelControllerWithMultipleResponses()
    {
        $response = \Symfony\Component\HttpFoundation\Response::create();
        $type = 'json';
        $header = 'application/json';
        $statusCode = 200;
        $validResponse = new Response(['type' => $type,'code'=>$statusCode]);
        $extraResponse = new Response(['type' => 'xml','code'=>400]);
        $reader = $this->getReaderStub([$validResponse,$extraResponse]);
        $event = $this->getKernelControllerEventStub([ControllerForActionListenerTest::class,'action']);

        $responseEvent = $this->createMock(FilterResponseEvent::class);
        $responseEvent->expects($this->any())
            ->method('getResponse')
            ->willReturn($response);

        $configuration = $this->getConfigurationResolverMock();
        $configuration->expects($this->once())
            ->method('getTypeFromKey')
            ->with('json')
            ->willReturn(new NegotiationResult($type,$header));

        $listener = new ActionListener($reader,$configuration);
        $listener->onKernelController($event);
        $listener->onKernelResponse($responseEvent);
        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertSame($header, $response->headers->get('Content-Type'));
        $this->assertSame($statusCode,$response->getStatusCode());
    }

    public function testKernelResponseBind()
    {
        $headers = ['name'=>'foo','value'=>'bar'];
        $response = \Symfony\Component\HttpFoundation\Response::create();
        $annotation = new Response([
            'headers' => [
                new ResponseHeader($headers)
            ]
        ]);
        $listener = new ActionListener($this->getReaderStub([$annotation]),$this->getConfigurationResolverMock());
        $event = $this->createMock(FilterResponseEvent::class);
        $event->expects($this->exactly(2))
            ->method('getResponse')
            ->willReturn($response);
        $listener->onKernelController($this->getKernelControllerEventStub(new ControllerForActionListenerTest()));
        $listener->onKernelResponse($event);
        $this->assertTrue($response->headers->has('foo'));
        $this->assertSame('bar',$response->headers->get('foo'));
    }

    public function testKernelResponseBindWithNoHeaders()
    {
        $response = \Symfony\Component\HttpFoundation\Response::create();
        $original = $response->headers->all();
        $annotation = new Response([]);
        $listener = new ActionListener($this->getReaderStub([$annotation]),$this->getConfigurationResolverMock());
        $event = $this->createMock(FilterResponseEvent::class);
        $event->expects($this->any())
            ->method('getResponse')
            ->willReturn($response);
        $listener->onKernelController($this->getKernelControllerEventStub(new ControllerForActionListenerTest()));
        $listener->onKernelResponse($event);
        $this->assertEquals($original,$response->headers->all());
    }

    public function testKernelResponseWithContext()
    {
        $response = \Symfony\Component\HttpFoundation\Response::create();
        $header = [
            'name' => 'key',
            'value' => '[(context1)]raw[(context1)]raw[(context2)]raw'
        ];
        $annotation = new Response([
            'headers' => [
                new ResponseHeader($header)
            ]
        ]);
        $listener = new ActionListener($this->getReaderStub([$annotation]),$this->getConfigurationResolverMock());
        $event = $this->createMock(FilterResponseEvent::class);
        $event->expects($this->any())
            ->method('getResponse')
            ->willReturn($response);
        $expected = 'value1rawvalue1rawvalue2raw';
        $listener->response('context1','value1')
            ->response('context2','value2');
        $listener->onKernelController($this->getKernelControllerEventStub(new ControllerForActionListenerTest()));
        $listener->onKernelResponse($event);
        $this->assertArrayHasKey('key',$response->headers->all());
        $this->assertSame($expected,$response->headers->get('key'));
    }

    public function testKernelViewBind()
    {
        $controllerResponse = 'response';
        $responseAnnotation = new Response([
            'value' => 'foo'
        ]);
        $response = new \Symfony\Component\HttpFoundation\Response('response');
        $response->headers->add([
            'Content-Type' => 'bar'
        ]);

        $event = $this->createMock(GetResponseForControllerResultEvent::class);
        $event->expects($this->once())
            ->method('getControllerResult')
            ->willReturn($controllerResponse);
        $event->expects($this->once())
            ->method('setResponse')
            ->with($response);


        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->once())
            ->method('getTypeFromKey')
            ->with('foo',0)
            ->willReturn(new NegotiationResult('foo','bar'));

        $actionListener = new ActionListener($this->getReaderStub([$responseAnnotation]),$configuration);
        $actionListener->onKernelController($this->getKernelControllerEventStub(new ControllerForActionListenerTest()));
        $actionListener->onKernelView($event);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testKernelViewBindWithInvalidResponseType()
    {
        $responseAnnotation = new Response([
            'value' => 'foo'
        ]);
        $response = new \Symfony\Component\HttpFoundation\Response('serialized_response');
        $response->headers->add([
            'Content-Type' => 'bar'
        ]);

        $event = $this->createMock(GetResponseForControllerResultEvent::class);


        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->once())
            ->method('getTypeFromKey')
            ->with('foo',0)
            ->willReturn(null);


        $actionListener = new ActionListener($this->getReaderStub([$responseAnnotation]),$configuration);
        $actionListener->onKernelController($this->getKernelControllerEventStub(new ControllerForActionListenerTest()));
        $actionListener->onKernelView($event);
    }

    public function testKernelViewBindWithSerializationContext()
    {
        $context = [
            'groups' => ['list']
        ];
        $controllerResponse = 'controller_response';
        $responseAnnotation = new Response([
            'value' => 'foo',
            'context' => $context
        ]);

        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->once())
            ->method('getTypeFromKey')
            ->with('foo',0)
            ->willReturn(new NegotiationResult('foo','bar'));

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serialize')
            ->with($controllerResponse,'foo',$context);

        $event = $this->createMock(GetResponseForControllerResultEvent::class);
        $event->expects($this->once())
            ->method('getControllerResult')
            ->willReturn($controllerResponse);
        $event->expects($this->once())
            ->method('setResponse');

        $listener = new ActionListener($this->getReaderStub([$responseAnnotation]),$configuration);
        $listener->setSerializer($serializer);
        $listener->onKernelController($this->getKernelControllerEventStub(new ControllerForActionListenerTest()));
        $listener->onKernelView($event);
    }

    public function testKernelViewBindWithSerializedResponse()
    {

        $controllerResponse = 'controller_response';
        $request = $this->getRequestMock();
        $event = new GetResponseForControllerResultEvent($this->createMock(HttpKernelInterface::class),$request,HttpKernelInterface::MASTER_REQUEST,$controllerResponse);

        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->once())
            ->method('resolveAcceptType')
            ->with($request)
            ->willReturn(new NegotiationResult('foo','bar'));

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serialize')
            ->with($controllerResponse,'foo')
            ->willReturn('serialized_response');

        $listener = new ActionListener($this->createMock(Reader::class),$configuration);
        $listener->setSerializer($serializer);
        $listener->onKernelView($event);
        $response = $event->getResponse();
        $this->assertArrayHasKey('content-type',$response->headers->all());
        $this->assertSame($response->headers->get('content-type'),'bar');
        $this->assertSame($response->getContent(),'serialized_response');
    }

    public function testKernelViewWithNoResponseAnnotations()
    {
        $controllerResponse = 'controller_response';
        $request = $this->getRequestMock();

        $configuration = $this->createMock(ConfigurationResolver::class);
        $configuration->expects($this->never())
            ->method('getTypeFromKey')
            ->with('foo',0)
            ->willReturn(new NegotiationResult('foo','bar'));

        $configuration->expects($this->once())
            ->method('resolveAcceptType')
            ->with($request)
            ->willReturn(new NegotiationResult('foo','bar'));

        $event = $this->createMock(GetResponseForControllerResultEvent::class);
        $event->expects($this->once())
            ->method('getControllerResult')
            ->willReturn($controllerResponse);
        $event->expects($this->once())
            ->method('setResponse');
        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $listener = new ActionListener($this->getReaderStub([]),$configuration);
        $listener->onKernelController($this->getKernelControllerEventStub(new ControllerForActionListenerTest()));
        $listener->onKernelView($event);
    }
}

class ControllerForActionListenerTest
{
    public function action(){}
    public function __invoke(){}
}