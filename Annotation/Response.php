<?php

namespace Alks\HttpExtraBundle\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * Class Response
 * @package Alks\HttpExtraBundle\Annotation
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @Target("METHOD")
 */
class Response extends Annotation
{
    /**
     * @var string
     */
    protected $type;
    /**
     * @var array<Alks\HttpExtraBundle\Annotation\ResponseHeader>
     */
    protected $headers;
    /**
     * @var array
     */
    protected $context;
    /**
     * @var int
     */
    protected $code;

    public function __construct(array $data)
    {
        if(isset($data['value']))
        {
            $data['type'] = $data['value'];
            unset($data['value']);
        }
        parent::__construct($data);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return ResponseHeader[]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }
}