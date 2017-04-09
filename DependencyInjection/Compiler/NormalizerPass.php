<?php

namespace Alks\HttpExtraBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class NormalizerPass
 * @package Alks\HttpExtraBundle\DependencyInjection\Compiler
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class NormalizerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if($container->has('alks_http.denormalizer'))
        {
            $class = $container->getParameterBag()->resolveValue(
                $container->findDefinition('alks_http.denormalizer')->getClass()
            );
            if(!is_subclass_of($class,'Symfony\Component\Serializer\Normalizer\DenormalizerInterface'))
            {
                throw new \InvalidArgumentException('"alks_http.denormalizer" must implement the Symfony\Component\Serializer\Normalizer\DenormalizerInterface');
            }
            return;
        }
        if($container->has('jms_serializer.serializer'))
        {
            $class = $container->getParameterBag()->resolveValue(
                $container->findDefinition('jms_serializer.serializer')->getClass()
            );
            if(method_exists($class,'fromArray'))
            {
                $container->setAlias('alks_http.denormalizer','alks_http.jms_denormalizer');
                return;
            }
        }
        if($container->has('serializer'))
        {
            $class = $container->getParameterBag()->resolveValue(
                $container->findDefinition('serializer')->getClass()
            );
            if(is_subclass_of($class,'Symfony\Component\Serializer\Normalizer\DenormalizerInterface'))
            {
                $container->setAlias('alks_http.denormalizer','serializer');
                return;
            }
        }
    }
}