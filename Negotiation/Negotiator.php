<?php

namespace Alks\HttpExtraBundle\Negotiation;
use Negotiation\Accept;
use Negotiation\AcceptLanguage;
use Negotiation\EncodingNegotiator;
use Negotiation\LanguageNegotiator;

/**
 * Default http negotiator.
 *
 * Class Negotiator
 * @package Alks\HttpExtraBundle\Negotiation
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class Negotiator implements NegotiationInterface
{
    /**
     * Negotiates the type source of the request (accept/content)
     *
     * @param array $priorities
     * @param string $headerValue
     * @return string|null
     */
    public function negotiateType(array $priorities, $headerValue)
    {
        if($this->areValidParameters($priorities,$headerValue))
        {
            $negotiator = new \Negotiation\Negotiator();
            /** @var Accept  $value */
            $value = $negotiator->getBest($headerValue,$priorities);
            if($value !== null)
            {
                return $value->getValue();
            }
        }
        return null;
    }

    /**
     * Checks if the parameters passed for negotiation are valid.
     *
     * @param array $priorities
     * @param $header
     * @return bool
     */
    private function areValidParameters(array $priorities, $header)
    {
        return !($header === null || !count($priorities) || trim($header) === '');
    }
}