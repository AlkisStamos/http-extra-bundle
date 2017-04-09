<?php

namespace Alks\HttpExtraBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class SerializerPass
 * @package Alks\HttpExtraBundle\DependencyInjection\Compiler
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class SerializerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if($container->has('alks_http.serializer'))
        {
            $class = $container->getParameterBag()->resolveValue(
                $container->findDefinition('alks_http.serializer')->getClass()
            );
            if(!is_subclass_of($class,'Symfony\Component\Serializer\SerializerInterface'))
            {
                throw new \InvalidArgumentException('"alks_http.serializer" must implement Symfony\Component\Serializer\SerializerInterface');
            }
            return;
        }
        if($container->has('jms_serializer.serializer'))
        {
            $class = $container->getParameterBag()->resolveValue(
                $container->findDefinition('jms_serializer.serializer')->getClass()
            );
            if(!is_subclass_of($class,'JMS\Serializer\SerializerInterface'))
            {
                throw new \InvalidArgumentException('"jm_serializer" must implement JMS\Serializer\SerializerInterface in order to be usable by alks_http');
            }
            $container->setAlias('alks_http.serializer','alks_http.jms_serializer');
            return;
        }
        if($container->has('serializer'))
        {
            $class = $container->getParameterBag()->resolveValue(
                $container->findDefinition('serializer')->getClass()
            );
            if(!is_subclass_of($class,'Symfony\Component\Serializer\SerializerInterface'))
            {
                throw new \InvalidArgumentException('The serializer class must implement Symfony\Component\Serializer\SerializerInterface in order to be usable by alks_http extension');
            }
            $container->setAlias('alks_http.serializer','serializer');
            return;
        }
    }
}