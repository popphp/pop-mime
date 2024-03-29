<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part\Header\Value;
use PHPUnit\Framework\TestCase;

class HeaderValueTest extends TestCase
{

    public function testScheme()
    {
        $headerValue = new Value();
        $headerValue->setScheme('Basic ');
        $this->assertTrue($headerValue->hasScheme());
        $this->assertEquals('Basic ', $headerValue->getScheme());
    }

    public function testHasParameters()
    {
        $headerValue = new Value('test', null, ['foo' => 'bar'], true);
        $this->assertTrue($headerValue->hasParameters());
        $this->assertTrue($headerValue->isForceQuote());
        $this->assertEquals('bar', $headerValue->getParameters()['foo']);
    }

    public function testGetDelimiter()
    {
        $headerValue = new Value('test', 'Basic ', ['foo' => 'bar']);
        $headerValue->setDelimiter(', ');
        $this->assertEquals(', ', $headerValue->getDelimiter());
        $this->assertEquals('Basic ', $headerValue->getScheme());
    }

    public function testParameterException()
    {
        $this->expectException('TypeError');
        $headerValue = new Value();
        $headerValue->setDelimiter(null);
        $params = $headerValue->getParametersAsString();
    }

    public function testParse()
    {
        $headerValue = Value::parse('Bearer jsakdcnjksadnjcksndjkncsdc; foo=bar');
        $this->assertEquals('Bearer ', $headerValue->getScheme());

    }

}