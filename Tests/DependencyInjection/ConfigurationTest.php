<?php

namespace Alks\HttpExtraBundle\Tests\DependencyInjection;
use Alks\HttpExtraBundle\DependencyInjection\Configuration;
use Alks\HttpExtraBundle\Tests\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * Class ConfigurationTest
 * @package Alks\HttpExtraBundle\Tests\DependencyInjection
 * @author Alkis Stamos <stamosalkis@gmail.com>
 */
class ConfigurationTest extends TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    public function testEmptyConfiguration()
    {
        $expected = [
            'types' => [],
            'headers' => [],
            'append_types' => []
        ];
        $configuration = new Configuration();
        $res = $this->processor->processConfiguration($configuration,[]);
        $this->assertEquals($expected,$res);
    }
    
    public function setUp()
    {
        parent::setUp();
        $this->processor = new Processor();
    }
}