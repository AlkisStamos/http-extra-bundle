<?php

namespace Alks\HttpExtraBundle\Serializer;
use JMS\Serializer\Context;
use JMS\Serializer\DeserializationContext;

/**
 * Class AbstractJMSAdapter
 * @package Alks\HttpExtraBundle\Serializer
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
abstract class AbstractJMSAdapter
{
    protected function mergeContext(array $context, Context $jmsContext)
    {
        if($jmsContext instanceof DeserializationContext && isset($context['maxDepth']) && isset($context['enable_max_depth']))
        {
            if($context['enable_max_depth'])
            {
                $jmsContext->enableMaxDepthChecks();
                for($i=0;$i<$context['maxDepth'];$i++)
                {
                    $jmsContext->increaseDepth();
                }
            }
        }
        foreach($context as $key=>$item)
        {
            $jmsContext->setAttribute($key,$item);
        }
        if(isset($context['version']))
        {
            $jmsContext->setVersion($context['version']);
        }
        if(isset($context['groups']))
        {
            $jmsContext->setGroups($context['groups']);
        }
        return $jmsContext;
    }
}