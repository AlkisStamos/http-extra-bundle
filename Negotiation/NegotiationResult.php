<?php

namespace Alks\HttpExtraBundle\Negotiation;
/**
 * Represents a negotiation result.
 *
 * Class NegotiatedValue
 * @package Alks\HttpExtraBundle\Negotiation
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class NegotiationResult
{
    /**
     * Small name of the negotiated value (json,xml,text etc)
     *
     * @var string
     */
    protected $name;
    /**
     * Value found in the request (text/json, application/json etc)
     *
     * @var string
     */
    protected $value;

    /**
     * NegotiatedValue constructor.
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}