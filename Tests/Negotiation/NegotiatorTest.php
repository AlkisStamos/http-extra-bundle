<?php

namespace Alks\HttpExtraBundle\Tests\Negotiation;
use Alks\HttpExtraBundle\Negotiation\Negotiator;
use Alks\HttpExtraBundle\Tests\TestCase;

/**
 * Class NegotiatorTest
 * @package Alks\HttpExtraBundle\Tests\Negotiation
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class NegotiatorTest extends TestCase
{
    private $_negotiator;

    private function getNegotiator($newInstance=false)
    {
        if($newInstance)
        {
            $this->_negotiator = null;
        }
        if($this->_negotiator === null)
        {
            $this->_negotiator = new Negotiator();
        }
        return $this->_negotiator;
    }

    public function testTypeNegotiation()
    {
        $priorities = $this->getPrioritiesFromValues($this->getTypes());
        $priorities[] = 'text/html';
        $against = [
            'foo' => null,
            'json' => null,
            'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' => 'text/html',
            'application/xml,application/xhtml+xml,text/html;q=0.9, text/plain;q=0.8,image/png,*/*;q=0.5' => 'application/xml',
            '*/*' => 'application/json',
            '*' => 'application/json',
            'text/html;q=0.5, application/xml;q=0.8, application/xhtml+xml;q=0.9, image/png, image/webp, image/jpeg, image/gif, image/x-xbitmap, */*;q=0.1' => 'application/xml'
        ];
        foreach($priorities as $priority)
        {
            $against[$priority] = $priority;
        }
        foreach($against as $key=>$value)
        {
            $this->assertNegotiateType($priorities,$key,$value);
        }
    }

    private function assertNegotiateType(array $priorities, $value, $expected)
    {
        $this->assertSame($expected,$this->getNegotiator()->negotiateType($priorities,$value));
    }

    public function testNegotiateTypeWithoutPriorities()
    {
        $this->assertNull(
            $this->getNegotiator()->negotiateType([],'text/html')
        );
        $this->assertNull(
            $this->getNegotiator()->negotiateType([],'anything')
        );
    }

    public function testNegotiateTypeWithoutHeader()
    {
        $this->assertNull(
            $this->getNegotiator()->negotiateType(
                self::getPrioritiesFromValues(self::getTypes()),null
            )
        );
    }

    public function testNegotiateTypeWithEmptyHeader()
    {
        $this->assertNull(
            $this->getNegotiator()->negotiateType(
                self::getPrioritiesFromValues(self::getTypes()),''
            )
        );
    }
}