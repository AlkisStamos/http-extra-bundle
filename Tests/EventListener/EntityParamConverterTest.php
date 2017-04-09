<?php

namespace Alks\HttpExtraBundle\Tests\EventListener;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Alks\HttpExtraBundle\Annotation\ActionParam;
use Alks\HttpExtraBundle\EventListener\ActionListener;
use Alks\HttpExtraBundle\EventListener\EntityParamConverter;
use Alks\HttpExtraBundle\Tests\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EntityParamConverterTest
 * @package Alks\HttpExtraBundle\Tests\EventListener
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class EntityParamConverterTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        if(!class_exists('\Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter'))
        {
            $this->markTestSkipped(
                'Framework extra bundle not loaded, skipping entity param converter tests'
            );
        }
    }

    public function testSupportsWithExistingActionParameter()
    {
        $configurationStub = $this->createMock(ParamConverter::class);
        $configurationStub->expects($this->once())
            ->method('getName')
            ->willReturn('foo');
        $listenerStub = $this->createMock(ActionListener::class);
        $listenerStub
            ->expects($this->once())
            ->method('getActionParameters')
            ->willReturn(['foo'=>'bar']);
        $converter = new EntityParamConverter($listenerStub);
        $this->assertTrue($converter->supports($configurationStub));
    }

    public function testSupportsWithNonExistingActionParameters()
    {
        $configurationStub = $this->createMock(ParamConverter::class);
        $configurationStub->expects($this->once())
            ->method('getName')
            ->willReturn('foo');
        $listenerStub = $this->createMock(ActionListener::class);
        $listenerStub
            ->expects($this->once())
            ->method('getActionParameters')
            ->willReturn(['bar'=>'baz']);
        $converter = new EntityParamConverter($listenerStub);
        $this->assertFalse($converter->supports($configurationStub));
    }

    public function testSupportsWithEmptyManagers()
    {
        $configurationStub = $this->createMock(ParamConverter::class);
        $listenerStub = $this->createMock(ActionListener::class);
        $listenerStub->expects($this->any())
            ->method('getActionParameters')
            ->willReturn([]);
        $converter = new EntityParamConverter($listenerStub);
        $this->assertFalse($converter->supports($configurationStub));
    }

    public function testApplyWithExistingActionParameter()
    {
        $configurationStub = $this->createMock(ParamConverter::class);
        $configurationStub->expects($this->any())
            ->method('getName')
            ->willReturn('foo');

        $parameterStub = new ActionParam([
            'bindTo' => 'foo',
        ]);

        $listenerStub = $this->createMock(ActionListener::class);
        $listenerStub->expects($this->any())
            ->method('getActionParameters')
            ->willReturn([
                'foo' => $parameterStub
            ]);

        $converter = new EntityParamConverter($listenerStub);
        $this->assertTrue($converter->apply($this->getRequestMock(),$configurationStub));
    }

    public function testApplyWithInvalidRouteName()
    {
        $configurationStub = $this->createMock(ParamConverter::class);
        $configurationStub->expects($this->any())
            ->method('getName')
            ->willReturn('foo');

        $parameterStub = new ActionParam([
            'bindTo' => 'foo',
            'route' => 'bar'
        ]);

        $listenerStub = $this->createMock(ActionListener::class);
        $listenerStub->expects($this->any())
            ->method('getActionParameters')
            ->willReturn([
                'foo' => $parameterStub
            ]);

        $request = $this->getRequestMock();
        $request->attributes = $this->createMock(ParameterBag::class);
        $request->attributes->expects($this->once())
            ->method('get')
            ->with('_route')
            ->willReturn('foo');

        $request->attributes->expects($this->once())
            ->method('has')
            ->with('_route')
            ->willReturn(true);

        $converter = new EntityParamConverter($listenerStub);
        $this->assertFalse($converter->apply($request,$configurationStub));
    }

    public function testApplyWithValidRouteName()
    {
        $configurationStub = $this->createMock(ParamConverter::class);
        $configurationStub->expects($this->any())
            ->method('getName')
            ->willReturn('foo');

        $parameterStub = new ActionParam([
            'bindTo' => 'foo',
            'route' => 'foo'
        ]);

        $listenerStub = $this->createMock(ActionListener::class);
        $listenerStub->expects($this->any())
            ->method('getActionParameters')
            ->willReturn([
                'foo' => $parameterStub
            ]);

        $request = $this->getRequestMock();
        $request->attributes = $this->createMock(ParameterBag::class);
        $request->attributes->expects($this->once())
            ->method('get')
            ->with('_route')
            ->willReturn('foo');

        $request->attributes->expects($this->once())
            ->method('has')
            ->with('_route')
            ->willReturn(true);

        $converter = new EntityParamConverter($listenerStub);
        $this->assertTrue($converter->apply($request,$configurationStub));
    }
}