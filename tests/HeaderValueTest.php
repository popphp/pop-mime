<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part\Header\Value;
use Pop\Mime\Part\Header;
use Pop\Mime\Part\Header\AddressList;
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

    public function testHasSchemeFalseWhenNotSet()
    {
        $headerValue = Value::parse('text/html');
        $this->assertFalse($headerValue->hasScheme());
    }

    public function testParseParameterWithDelimiterInsideQuotes()
    {
        $headerValue = Value::parse('attachment; filename="invoice; final.pdf"');
        $this->assertEquals('attachment', $headerValue->getValue());
        $this->assertEquals('invoice; final.pdf', $headerValue->getParameter('filename'));
    }

    public function testParseDigestWithCommaDelimitedParametersAndNoLeadingValue()
    {
        $headerValue = Value::parse('Digest username="my_username", realm="my_realm", nonce="my-nonce-123456"');
        $this->assertEquals('Digest ', $headerValue->getScheme());
        $this->assertNull($headerValue->getValue());
        $this->assertEquals('my_username', $headerValue->getParameter('username'));
        $this->assertEquals('my_realm', $headerValue->getParameter('realm'));
        $this->assertEquals('my-nonce-123456', $headerValue->getParameter('nonce'));
    }

    public function testParseCommaSeparatedListWithoutKeyValuePairsIsNotLost()
    {
        $headerValue = Value::parse('gzip, deflate');
        $this->assertEquals('gzip, deflate', $headerValue->getValue());
    }

    public function testGetDecodedValue()
    {
        $headerValue = new Value('=?UTF-8?B?SGVsbG8=?=');
        $this->assertEquals('Hello', $headerValue->getDecodedValue());
    }

    public function testGetDecodedValueReturnsNullWhenNoValue()
    {
        $headerValue = new Value();
        $this->assertNull($headerValue->getDecodedValue());
    }

    public function testGetDecodedValueDoesNotMutateStoredRawValue()
    {
        $headerValue = new Value('=?UTF-8?B?SGVsbG8=?=');
        $headerValue->getDecodedValue();
        $this->assertEquals('=?UTF-8?B?SGVsbG8=?=', $headerValue->getValue());
    }

    public function testRenderAutoEncodesNonAsciiValue()
    {
        $headerValue = new Value('Héllo');
        $rendered    = $headerValue->render();
        $this->assertStringStartsWith('=?UTF-8?B?', $rendered);
    }

    public function testRenderLeavesAsciiValueUnchanged()
    {
        $headerValue = new Value('text/html');
        $this->assertEquals('text/html', $headerValue->render());
    }

    public function testRenderDoesNotAutoEncodeParameterValues()
    {
        $headerValue = new Value('attachment', null, ['filename' => 'Héllo.pdf']);
        $rendered    = $headerValue->render();
        $this->assertStringContainsString('filename=', $rendered);
        $this->assertStringNotContainsString('=?UTF-8?B?', $rendered);
    }

    public function testRenderWithAddressHeaderNameOnlyEncodesDisplayName()
    {
        $headerValue = new Value('Björn Müller <bjorn@example.com>');
        $rendered    = $headerValue->render('From');
        $this->assertStringContainsString('<bjorn@example.com>', $rendered);
        $this->assertStringStartsWith('=?UTF-8?B?', $rendered);
    }

    public function testRenderWithNonAddressHeaderNameEncodesWholeValue()
    {
        $headerValue = new Value('Héllo Wörld');
        $rendered    = $headerValue->render('Subject');
        $this->assertStringStartsWith('=?UTF-8?B?', $rendered);
        $this->assertStringNotContainsString('Wörld', $rendered);
    }

    public function testRenderWithNoHeaderNameStillEncodesWholeValue()
    {
        $headerValue = new Value('Héllo');
        $this->assertEquals($headerValue->render(), $headerValue->render(null));
    }

    public function testRenderAddressHeaderEncodesAllDisplayNamesNotJustFirst()
    {
        $headerValue = new Value('Björn Müller <bjorn@example.com>, Jürgen Groß <jurgen@example.com>');
        $rendered    = $headerValue->render('To');

        $this->assertEquals(2, substr_count($rendered, '=?UTF-8?B?'));
        $this->assertStringContainsString('<bjorn@example.com>', $rendered);
        $this->assertStringContainsString('<jurgen@example.com>', $rendered);
    }

    public function testMultiRecipientHeaderRoundTripsAllAddressesAfterEncoding()
    {
        $header = new Header(
            'To',
            'John Doe <john@example.com>, Jürgen Groß <jurgen@example.com>, "Smith, Ana" <ana@example.com>'
        );
        $rendered = $header->render();
        $parsed   = Header::parse($rendered);

        $addressList = AddressList::parse($parsed->getValue(0)->getValue());

        $this->assertEquals(3, $addressList->count());
        $this->assertEquals('john@example.com', $addressList->getAddresses()[0]->getAddress());
        $this->assertEquals('jurgen@example.com', $addressList->getAddresses()[1]->getAddress());
        $this->assertEquals('ana@example.com', $addressList->getAddresses()[2]->getAddress());
    }

}