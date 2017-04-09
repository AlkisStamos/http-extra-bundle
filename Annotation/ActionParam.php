<?php

namespace Alks\HttpExtraBundle\Annotation;
use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Base annotation class for annotation regarding controller method arguments.
 *
 * Class ActionParam
 * @package Alks\HttpExtraBundle\Annotation
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class ActionParam extends Annotation
{
    /**
     * The name of the controller argument that will bind the action parameter value
     *
     * @Required
     * @var string
     */
    protected $bindTo;
    /**
     * The type of the parameter. If not defined the php typehint will be used.
     *
     * @var string
     */
    protected $type;
    /**
     * Defines if the action param should use a default value in case no relevant data are found in the request.
     *
     * @var boolean
     */
    protected $hasDefaultValue;
    /**
     * Defines the default value to be bound to the argument if no relevant data are found in the request.
     *
     * @var string
     */
    protected $defaultValue;
    /**
     * Binds the action param to a specific route name. This may come in handy for actions that define more than one
     * route annotations
     *
     * @var string
     */
    protected $route;

    /**
     * ActionParam constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        if(!$this->hasDefaultValue)
        {
            $this->hasDefaultValue = trim($this->defaultValue) !== '';
        }
    }

    /**
     * @return string
     */
    public function getBindTo()
    {
        return $this->bindTo;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return boolean
     */
    public function isHasDefaultValue()
    {
        return $this->hasDefaultValue;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }
}