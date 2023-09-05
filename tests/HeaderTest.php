<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{

    public function testWrap()
    {
        $header = new Header('Header');
        $header->setWrap(100);
        $this->assertTrue($header->hasWrap());
        $this->assertEquals(100, $header->getWrap());
    }

    public function testIndent()
    {
        $header = new Header('Header');
        $header->setIndent('    ');
        $this->assertTrue($header->hasIndent());
        $this->assertEquals('    ', $header->getIndent());
    }

    public function testMultipleValues1()
    {
        $header = new Header('Set-Cookie', ['123456', '987654']);
        $headerValueStrings = $header->getValuesAsStrings(';');
        $headerString       = $header->render();
        $this->assertStringContainsString('Set-Cookie: 123456', $headerString);
        $this->assertStringContainsString('Set-Cookie: 987654', $headerString);
        $this->assertStringContainsString('123456', $headerValueStrings);
        $this->assertStringContainsString('987654', $headerValueStrings);
    }

    public function testMultipleValues2()
    {
        $header = new Header('Set-Cookie');
        $header->addValues(['123456', '987654']);

        $this->assertEquals(1, $header->getValueIndex('987654'));
    }

    public function testMultipleValuesWithWrap()
    {
        $header = new Header('Set-Cookie');
        $header->addValues(['123456', '987654']);
        $header->setWrap(100);
        $headerString = $header->render();
        $this->assertStringContainsString('Set-Cookie: 123456', $headerString);
        $this->assertStringContainsString('Set-Cookie: 987654', $headerString);
    }

    public function testMultipleValuesWithParams()
    {
        $header = new Header('Set-Cookie');
        $header->addValue('123456', null, ['foo' => 'bar']);
        $header->addValue('987654', null, ['baz' => '123']);
        $headerString = $header->render();
        $this->assertStringContainsString('Set-Cookie: 123456; foo=bar', $headerString);
        $this->assertStringContainsString('Set-Cookie: 987654; baz=123', $headerString);
    }

    public function testParseMultipleValues()
    {
        $header = new Header('Set-Cookie');
        $header->addValues(['123456', '987654']);
        $headerString = $header->render();
        $parsedHeader = Header::parse($headerString);
        $this->assertEquals(2, count($parsedHeader->getValues()));
    }

    public function testParseMultipleValuesWithParams()
    {
        $header = new Header('Set-Cookie');
        $header->addValue('123456', null, ['foo' => 'bar']);
        $header->addValue('987654', null, ['baz' => '123']);
        $headerString = $header->render();
        $parsedHeader = Header::parse($headerString);
        $this->assertEquals(2, count($parsedHeader->getValues()));
    }

    public function testGetParametersAsString()
    {
        $headerValue = new Header\Value('attachment', null, ['filename' => 'some file.pdf']);
        $header = new Header('Content-Disposition', $headerValue);
        $this->assertEquals('filename="some file.pdf"', $headerValue->getParametersAsString());
    }

    public function testHasValueAtIndex()
    {
        $headerValue = new Header\Value('attachment', null, ['filename' => 'some file.pdf']);
        $header = new Header('Content-Disposition', $headerValue);
        $this->assertTrue($header->hasValueAtIndex(0));
    }

    public function testGetValueAsObject()
    {
        $headerValue = new Header\Value('attachment', null, ['filename' => 'some file.pdf']);
        $header = new Header('Content-Disposition', $headerValue);
        $this->assertTrue(is_string($header->getValueAsString()));
    }

}