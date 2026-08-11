<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part\Header\AddressList;
use PHPUnit\Framework\TestCase;

class HeaderAddressListTest extends TestCase
{

    public function testParseMultipleAddresses()
    {
        $list = AddressList::parse('John Doe <john@example.com>, Jane Smith <jane@example.com>');

        $this->assertEquals(2, $list->count());
        $this->assertEquals('John Doe', $list->getAddresses()[0]->getName());
        $this->assertEquals('john@example.com', $list->getAddresses()[0]->getAddress());
        $this->assertEquals('Jane Smith', $list->getAddresses()[1]->getName());
        $this->assertEquals('jane@example.com', $list->getAddresses()[1]->getAddress());
    }

    public function testParseQuotedDisplayNameWithCommaDoesNotSplitList()
    {
        $list = AddressList::parse('"Doe, John" <john@example.com>, Jane Smith <jane@example.com>');

        $this->assertEquals(2, $list->count());
        $this->assertEquals('Doe, John', $list->getAddresses()[0]->getName());
        $this->assertEquals('Jane Smith', $list->getAddresses()[1]->getName());
    }

    public function testParseSingleAddress()
    {
        $list = AddressList::parse('john@example.com');

        $this->assertEquals(1, $list->count());
        $this->assertNull($list->getAddresses()[0]->getName());
        $this->assertEquals('john@example.com', $list->getAddresses()[0]->getAddress());
    }

    public function testParseEmptyStringProducesEmptyList()
    {
        $list = AddressList::parse('');
        $this->assertEquals(0, $list->count());
    }

    public function testRenderJoinsMultipleAddressesWithCommaSpace()
    {
        $list = new AddressList();
        $list->addAddress('john@example.com', 'John Doe');
        $list->addAddress('jane@example.com', 'Jane Smith');

        $this->assertEquals('John Doe <john@example.com>, Jane Smith <jane@example.com>', $list->render());
    }

    public function testParseThenRenderRoundTrips()
    {
        $original = 'John Doe <john@example.com>, "Doe, Jane" <jane@example.com>';
        $list     = AddressList::parse($original);
        $this->assertEquals($original, $list->render());
    }

    public function testParseTreatsGroupSyntaxAsOneOpaqueAddress()
    {
        $list = AddressList::parse('Team: jurgen@example.com, klaus@example.com;');
        $this->assertEquals(1, $list->count());
        $this->assertNull($list->getAddresses()[0]->getName());
        $this->assertEquals('Team: jurgen@example.com, klaus@example.com;', $list->getAddresses()[0]->getAddress());
    }

    public function testParseDoesNotTreatUnquotedColonWithoutTerminatorAsGroup()
    {
        $list = AddressList::parse('IT: Support <support@example.com>, Sales: John <john@sales.com>');

        $this->assertEquals(2, $list->count());
        $this->assertEquals('support@example.com', $list->getAddresses()[0]->getAddress());
        $this->assertEquals('john@sales.com', $list->getAddresses()[1]->getAddress());
    }

}
