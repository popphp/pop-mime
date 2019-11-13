<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{

    public function testParameters()
    {
        $header = new Header();
        $header->addParameters([
            'foo' => 'bar',
            'baz' => 123
        ]);
        $this->assertTrue($header->hasParameters());
        $this->assertEquals(2, count($header->getParameters()));
    }

    public function testWrap()
    {
        $header = new Header();
        $header->setWrap(100);
        $this->assertTrue($header->hasWrap());
        $this->assertEquals(100, $header->getWrap());
    }

    public function testIndent()
    {
        $header = new Header();
        $header->setIndent('    ');
        $this->assertTrue($header->hasIndent());
        $this->assertEquals('    ', $header->getIndent());
    }

}