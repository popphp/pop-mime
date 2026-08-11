<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part\Header\Address;
use PHPUnit\Framework\TestCase;

class HeaderAddressTest extends TestCase
{

    public function testParseBareAddress()
    {
        $address = Address::parse('john@example.com');
        $this->assertNull($address->getName());
        $this->assertEquals('john@example.com', $address->getAddress());
    }

    public function testParseNameAndAddress()
    {
        $address = Address::parse('John Doe <john@example.com>');
        $this->assertEquals('John Doe', $address->getName());
        $this->assertEquals('john@example.com', $address->getAddress());
    }

    public function testParseQuotedNameWithComma()
    {
        $address = Address::parse('"Doe, John" <john@example.com>');
        $this->assertEquals('Doe, John', $address->getName());
        $this->assertEquals('john@example.com', $address->getAddress());
    }

    public function testParseDecodesEncodedWordDisplayName()
    {
        $rendered = (new Address('john@example.com', 'Björn Müller'))->render();
        $parsed   = Address::parse($rendered);

        $this->assertEquals('Björn Müller', $parsed->getName());
        $this->assertEquals('john@example.com', $parsed->getAddress());
    }

    public function testParseMalformedAngleBracketsDegradesToBareAddress()
    {
        $address = Address::parse('John Doe <john@example.com');
        $this->assertNull($address->getName());
        $this->assertEquals('John Doe <john@example.com', $address->getAddress());
    }

    public function testRenderWithNoNameReturnsBareAddress()
    {
        $address = new Address('john@example.com');
        $this->assertEquals('john@example.com', $address->render());
    }

    public function testRenderAsciiNameNeedingNoQuoting()
    {
        $address = new Address('john@example.com', 'John Doe');
        $this->assertEquals('John Doe <john@example.com>', $address->render());
    }

    public function testRenderAsciiNameNeedingQuoting()
    {
        $address = new Address('john@example.com', 'Doe, John');
        $this->assertEquals('"Doe, John" <john@example.com>', $address->render());
    }

    public function testRenderNonAsciiNameIsEncodedNotQuoted()
    {
        $address  = new Address('john@example.com', 'Björn Müller');
        $rendered = $address->render();

        $this->assertStringStartsWith('=?UTF-8?B?', $rendered);
        $this->assertStringNotContainsString('"', $rendered);
        $this->assertStringEndsWith('<john@example.com>', $rendered);
    }

    public function testParseThenRenderRoundTripsQuotedName()
    {
        $original = '"Doe, John" <john@example.com>';
        $address  = Address::parse($original);
        $this->assertEquals($original, $address->render());
    }

    public function testParseDegradesToBareAddressWhenTrailingContentAfterAngleAddr()
    {
        $address = Address::parse('Name <a@example.com> <c@example.com>');
        $this->assertNull($address->getName());
        $this->assertEquals('Name <a@example.com> <c@example.com>', $address->getAddress());
    }

}
