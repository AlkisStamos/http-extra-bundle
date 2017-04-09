<?php

namespace Alks\HttpExtraBundle\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Annotation class that
 *
 * @Annotation
 * Class RequestData
 * @package Alks\HttpExtraBundle\Annotation
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @Target("METHOD")
 */
class RequestData extends ActionParam
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}