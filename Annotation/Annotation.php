<?php

namespace Alks\HttpExtraBundle\Annotation;
/**
 * Base annotation class for the http extra bundle. All annotation classes of the bundle extend this class and use the
 * same constructor.
 *
 * Class Annotation
 * @package Alks\HttpExtraBundle\Annotation
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class Annotation
{
    /**
     * General constructor for all annotations in the package
     * 
     * Annotation constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach($data as $key=>$value)
        {
            if(!property_exists($this,$key))
            {
                throw new \BadMethodCallException(sprintf('Unknown property "%s" on annotation "%s".', $key, get_class($this)));
            }
            $this->{$key} = $value;
        }
    }
}