<?php

namespace Alks\HttpExtraBundle\Tests;

use Alks\HttpExtraBundle\DependencyInjection\Compiler\NormalizerPass;
use Alks\HttpExtraBundle\DependencyInjection\Compiler\SerializerPass;
use Alks\HttpExtraBundle\AlksHttpExtraBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class HttpExtraBundleTest
 * @package Alks\HttpExtraBundle\Tests
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class HttpExtraBundleTest extends TestCase
{
    public function testBuild()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->exactly(2))
            ->method('addCompilerPass')
            ->with($this->logicalOr(
                $this->isInstanceOf(SerializerPass::class),
                $this->isInstanceOf(NormalizerPass::class)
            ));
        $bundle = new AlksHttpExtraBundle();
        $bundle->build($container);
    }
}