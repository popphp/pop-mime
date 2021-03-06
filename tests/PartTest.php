<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part;
use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;
use PHPUnit\Framework\TestCase;

class PartTest extends TestCase
{

    public function testConstructor()
    {
        $header = new Header('Content-Type', 'text/plain');
        $body   = new Body('Hello World!');
        $part   = new Part($header, $body);
        $this->assertInstanceOf('Pop\Mime\Part', $part);
        $this->assertTrue($part->hasHeader('Content-Type'));
        $this->assertTrue($part->hasBody());
        $this->assertInstanceOf('Pop\Mime\Part\Body', $part->getBody());
    }

    public function testConstructorWithArrays()
    {
        $header  = new Header('Content-Type', 'text/plain');
        $subPart = new Part();
        $part   = new Part([$header, $subPart]);
        $this->assertInstanceOf('Pop\Mime\Part', $part);
        $this->assertTrue($part->hasHeader('Content-Type'));
        $this->assertTrue($part->hasParts());
        $this->assertEquals(1, count($part->getParts()));
    }

    public function testAttachment()
    {
        $part   = new Part();
        $subPart = new Part();
        $subPart->addFile(__DIR__ . '/tmp/test.txt');
        $part->addPart($subPart);
        $this->assertTrue($part->hasAttachment());
        $this->assertEquals('Hello World!', trim($subPart->getContents()));
    }

    public function testGetContentsWithUrlEncoding()
    {
        $part = new Part();
        $body = new Body('admin@something%testwhat/ever', Body::URL);
        $part->setBody($body);
        $this->assertStringContainsString('admin%40something%25testwhat%2Fever', $part->render());
        $this->assertEquals('admin@something%testwhat/ever', $part->getContents());
    }

    public function testGetFilenameFromName()
    {
        $header = Header::parse('Content-Disposition: attachment; name=file.txt');
        $part   = new Part($header);
        $part->setBody(new Body(file_get_contents(__DIR__ . '/tmp/test.txt')));
        $part->getBody()->setAsFile(true);
        $this->assertEquals('file.txt', $part->getFilename());
    }

    public function testGetFilenameFromContentType1()
    {
        $header = Header::parse('Content-Type: text/plain; name=file.txt');
        $part   = new Part($header);
        $part->setBody(new Body(file_get_contents(__DIR__ . '/tmp/test.txt')));
        $part->getBody()->setAsFile(true);
        $this->assertEquals('file.txt', $part->getFilename());
    }

    public function testGetFilenameFromContentType2()
    {
        $header = Header::parse('Content-Type: text/plain; filename=file.txt');
        $part   = new Part($header);
        $part->setBody(new Body(file_get_contents(__DIR__ . '/tmp/test.txt')));
        $part->getBody()->setAsFile(true);
        $this->assertEquals('file.txt', $part->getFilename());
    }

    public function testGetFilenameFromContentDescription1()
    {
        $header = Header::parse('Content-Description: text/plain; name=file.txt');
        $part   = new Part($header);
        $part->setBody(new Body(file_get_contents(__DIR__ . '/tmp/test.txt')));
        $part->getBody()->setAsFile(true);
        $this->assertEquals('file.txt', $part->getFilename());
    }

    public function testGetFilenameFromContentDescription2()
    {
        $header = Header::parse('Content-Description: text/plain; filename=file.txt');
        $part   = new Part($header);
        $part->setBody(new Body(file_get_contents(__DIR__ . '/tmp/test.txt')));
        $part->getBody()->setAsFile(true);
        $this->assertEquals('file.txt', $part->getFilename());
    }

    public function testGetFilenameDecoded()
    {
        $header = Header::parse('Content-Disposition: attachment; name=UTFfile.txt');
        $part   = new Part($header);
        $part->setBody(new Body(file_get_contents(__DIR__ . '/tmp/test.txt')));
        $part->getBody()->setAsFile(true);
        $this->assertEquals('UTFfile.txt', $part->getFilename());
    }

    public function testRemoveHeader()
    {
        $header = Header::parse('Content-Type: text/plain');
        $part   = new Part($header);
        $this->assertTrue($part->hasHeader('Content-Type'));
        $part->removeHeader('Content-Type');
        $this->assertFalse($part->hasHeader('Content-Type'));
    }

    public function testGetHeadersAsArray()
    {
        $header = Header::parse('Content-Disposition: attachment; name=file.txt');
        $part   = new Part($header);
        $headerAry = $part->getHeadersAsArray();
        $this->assertTrue(isset($headerAry['Content-Disposition']));
        $this->assertEquals('attachment; name=file.txt', $headerAry['Content-Disposition']);
    }

}