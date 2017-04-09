<?php

namespace Alks\HttpExtraBundle\Annotation;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * Class RequestParam
 * @package Alks\HttpExtraBundle\Annotation
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @Target("METHOD")
 */
class RequestParam extends ActionParam
{
    /**
     * @Required
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $repository;
    /**
     * @var string
     */
    protected $findBy;
    /**
     * @var string
     */
    protected $manager;

    public function __construct(array $data)
    {
        if (isset($data['value'])) {
            $data['bindTo'] = $data['value'];
            unset($data['value']);
        }
        parent::__construct($data);
        if($this->name === null)
        {
            $this->name = $this->bindTo;
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return string
     */
    public function getFindBy()
    {
        return $this->findBy;
    }

    /**
     * @return string
     */
    public function getManager()
    {
        return $this->manager;
    }
}