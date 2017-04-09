<?php

namespace Alks\HttpExtraBundle\Negotiation;

/**
 * The main negotiation provider template.
 *
 * Interface NegotiationInterface
 * @package Alks\HttpExtraBundle\Negotiation
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
interface NegotiationInterface
{
    /**
     * Negotiates the type source of the request (accept/content)
     *
     * @param array $priorities
     * @param string $headerValue
     * @return string|null
     */
    public function negotiateType(array $priorities, $headerValue);
}