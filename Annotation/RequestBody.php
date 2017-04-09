<?php

namespace Alks\HttpExtraBundle\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Annotation class to bind the request body to an action argument.
 *
 * @Annotation
 * Class RequestBody
 * @package Alks\HttpExtraBundle\Annotation
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @Target("METHOD")
 */
class RequestBody extends ActionParam
{
    /**
     * Defines the format of the request body (eg json)
     *
     * @var string
     */
    protected $format;
    /**
     * Flag that instructs the resolver to validate the request body after the deserialization.
     *
     * @var boolean
     */
    protected $validate;

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @return boolean
     */
    public function shouldValidate()
    {
        return $this->validate;
    }
}