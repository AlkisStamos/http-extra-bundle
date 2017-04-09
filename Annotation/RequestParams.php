<?php

namespace Alks\HttpExtraBundle\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * Class RequestParams
 * @package Alks\HttpExtraBundle\Annotation
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @Target("METHOD")
 */
class RequestParams extends Annotation
{
    /**
     * @var array<Alks\HttpExtraBundle\Annotation\RequestParam>
     */
    protected $params;

    public function __construct(array $data)
    {
        if(isset($data['value']))
        {
            if(is_array($data['value']))
            {
                $this->params = [];
                foreach($data['value'] as $value)
                {
                    $this->params[] = new RequestParam([
                        'value' => $value
                    ]);
                }
            }
        }
        else
        {
            parent::__construct($data);
        }
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}