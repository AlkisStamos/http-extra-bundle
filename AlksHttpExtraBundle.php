<?php

namespace Alks\HttpExtraBundle;

use Alks\HttpExtraBundle\DependencyInjection\Compiler\ArgumentResolverPass;
use Alks\HttpExtraBundle\DependencyInjection\Compiler\NormalizerPass;
use Alks\HttpExtraBundle\DependencyInjection\Compiler\SerializerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class AlksHttpExtraBundle
 * @package Alks\HttpExtraBundle
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class AlksHttpExtraBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new SerializerPass());
        $container->addCompilerPass(new NormalizerPass());
    }
}
