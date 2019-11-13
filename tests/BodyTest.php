<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part\Body;
use PHPUnit\Framework\TestCase;

class BodyTest extends TestCase
{

    public function testConstructor()
    {
        $body = new Body('Hello World!', null, 76);
        $this->assertInstanceOf('Pop\Mime\Part\Body', $body);
        $this->assertTrue($body->hasSplit());
        $this->assertEquals(76, $body->getSplit());
        $this->assertTrue($body->hasContent());
        $this->assertNull($body->getEncoding());
        $this->assertFalse($body->hasEncoding());
    }

    public function testFileException()
    {
        $this->expectException('Pop\Mime\Part\Exception');
        $body = new Body();
        $body->setContentFromFile('bad.txt');
    }

    public function testToString()
    {
        $body = new Body('Hello World!');
        $this->assertEquals('Hello World!', (string)$body);
    }

}