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

    public function testParseFilenameWithSemicolonInsideQuotes()
    {
        $header = Header::parse('Content-Disposition: attachment; filename="invoice; final.pdf"');
        $this->assertEquals('invoice; final.pdf', $header->getValue(0)->getParameter('filename'));
    }

    public function testParseNotFooledByRepeatedNameWithinASingleLineValue()
    {
        $header = Header::parse('X-Note: value mentions X-Note: again in the text');
        $this->assertEquals(1, count($header->getValues()));
        $this->assertEquals('value mentions X-Note: again in the text', $header->getValueAsString());
    }

    public function testFoldNeverBreaksInsideAQuotedStringValue()
    {
        $headerValue = new Header\Value('attachment', null, [
            'filename' => 'a very long descriptive file name that needs quoting.pdf'
        ]);
        $header = new Header('Content-Disposition', $headerValue);
        $header->setWrap(40)->setIndent(' ');

        $rendered = $header->render();
        $lines    = explode("\r\n", $rendered);

        foreach ($lines as $line) {
            $this->assertEquals(0, substr_count($line, '"') % 2);
        }
    }

    public function testFoldedOutputUnfoldsBackToTheSameValue()
    {
        $headerValue = new Header\Value('form-data');
        $headerValue->addParameter('name', 'image')
            ->addParameter('filename', '/tmp/some image.jpg')
            ->addParameter('foo', 'Some other param');
        $header = new Header('Content-Disposition', $headerValue);
        $header->setWrap(40)->setIndent("\t");

        $rendered = $header->render();
        $parsed   = Header::parse($rendered);

        $this->assertEquals('form-data', $parsed->getValue(0)->getValue());
        $this->assertEquals('image', $parsed->getValue(0)->getParameter('name'));
        $this->assertEquals('/tmp/some image.jpg', $parsed->getValue(0)->getParameter('filename'));
        $this->assertEquals('Some other param', $parsed->getValue(0)->getParameter('foo'));
    }

    public function testParseThenRenderRoundTripsPlainKeyValueHeader()
    {
        $original = 'Set-Cookie: sessionid=abc123';
        $header   = Header::parse($original);
        $this->assertEquals($original, $header->render());
    }

    public function testParseThenRenderPreservesParentheticalTextInUnstructuredValue()
    {
        $original = 'Subject: Order #123 (updated) today';
        $header   = Header::parse($original);
        $this->assertEquals($original, $header->render());
    }

    public function testParseThenRenderPreservesMixedDelimiterList()
    {
        $original = 'Accept-Encoding: gzip, deflate; q=0.5';
        $header   = Header::parse($original);
        $this->assertEquals($original, $header->render());
    }

    public function testParseThenRenderRoundTripsQuotedParameterWithDelimiter()
    {
        $original = 'Content-Disposition: attachment; filename="a;b.pdf"';
        $header   = Header::parse($original);
        $this->assertEquals($original, $header->render());
    }

    public function testFoldDoesNotSplitEncodedWordAndRoundTrips()
    {
        $original = 'Héllo Wörld ünd mehr text hier damit es lang genug wird zum falten';
        $header   = new Header('Subject', $original);
        $header->setWrap(76)->setIndent("\t");

        $rendered = $header->render();
        $parsed   = Header::parse($rendered);

        $this->assertEquals($original, $parsed->getValue(0)->getDecodedValue());
    }

    public function testFromHeaderRenderPreservesAddressWhenDisplayNameIsNonAscii()
    {
        $header   = new Header('From', 'Björn Müller <bjorn@example.com>');
        $rendered = $header->render();
        $this->assertStringContainsString('<bjorn@example.com>', $rendered);
        $this->assertStringContainsString('=?UTF-8?B?', $rendered);
    }

    public function testFoldNeverBreaksInsideAngleAddr()
    {
        $header = new Header(
            'References',
            '<aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa@mail.example.com> <bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb@mail.example.com>'
        );
        $header->setWrap(76)->setIndent("\t");

        $rendered = $header->render();
        $unfolded = Header\Lexer::unfold($rendered);

        $this->assertStringNotContainsString('< ', $unfolded);
        $this->assertStringNotContainsString(' >', $unfolded);
        $this->assertStringContainsString('<aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa@mail.example.com>', $unfolded);
        $this->assertStringContainsString('<bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb@mail.example.com>', $unfolded);
    }

}