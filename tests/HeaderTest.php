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

    public function testMultipleValues()
    {
        $header = new Header('Set-Cookie', ['123456', '987654']);
        $headerString = $header->render();
        $this->assertContains('Set-Cookie: 123456', $headerString);
        $this->assertContains('Set-Cookie: 987654', $headerString);
    }

    public function testMultipleValuesWithWrap()
    {
        $header = new Header('Set-Cookie', ['123456', '987654']);
        $header->setWrap(100);
        $headerString = $header->render();
        $this->assertContains('Set-Cookie: 123456', $headerString);
        $this->assertContains('Set-Cookie: 987654', $headerString);
    }

    public function testMultipleValuesWithParams()
    {
        $header = new Header('Set-Cookie', ['123456', '987654'], ['foo' => 'bar']);
        $headerString = $header->render();
        $this->assertContains('Set-Cookie: 123456; foo=bar', $headerString);
        $this->assertContains('Set-Cookie: 987654; foo=bar', $headerString);
    }

    public function testParseMultipleValues()
    {
        $header = new Header('Set-Cookie', ['123456', '987654']);
        $headerString = $header->render();
        $parsedHeader = Header::parse($headerString);
        $this->assertEquals(2, count($parsedHeader->getValue()));
    }

    public function testParseMultipleValuesWithParams()
    {
        $header = new Header('Set-Cookie', ['123456', '987654'], ['foo' => 'bar']);
        $headerString = $header->render();
        $parsedHeader = Header::parse($headerString);
        $this->assertEquals(2, count($parsedHeader->getValue()));
    }

    public function testGetParametersAsString()
    {
        $header = new Header('Content-Disposition', 'attachment', ['filename' => 'some file.pdf']);
        $this->assertEquals('filename="some file.pdf"', $header->getParametersAsString());
    }

}