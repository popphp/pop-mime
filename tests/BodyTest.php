<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part\Body;
use Pop\Mime\Part\Body\Encoding;
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

    public function testSetContentFromFileDoesNotThrowImmediately()
    {
        $body = new Body();
        $body->setContentFromFile('bad.txt');
        $this->assertInstanceOf('Pop\Mime\Part\Body', $body);
    }

    public function testGetContentThrowsForMissingFileOnAccess()
    {
        $this->expectException('Pop\Mime\Part\Exception');
        $body = new Body();
        $body->setContentFromFile('bad.txt');
        $body->getContent();
    }

    public function testRenderThrowsForMissingFileOnAccess()
    {
        $this->expectException('Pop\Mime\Part\Exception');
        $body = new Body();
        $body->setContentFromFile('bad.txt', Encoding::BASE64);
        $body->render();
    }

    public function testToString()
    {
        $body = new Body('Hello World!');
        $this->assertEquals('Hello World!', (string)$body);
    }

    public function testRawUrlEncode()
    {
        $body = new Body('admin@something%test+what/ever', Encoding::RAW_URL);
        $this->assertEquals('admin%40something%25test%2Bwhat%2Fever', (string)$body);
    }

    public function testSetEncodingRejectsNonEncodingValue()
    {
        $this->expectException(\TypeError::class);
        $body = new Body('test');
        $body->setEncoding('BASE64');
    }

    public function testIsEncodingHelpers()
    {
        $this->assertTrue((new Body('x', Encoding::BASE64))->isBase64Encoding());
        $this->assertTrue((new Body('x', Encoding::QUOTED_PRINTABLE))->isQuotedPrintableEncoding());
        $this->assertTrue((new Body('x', Encoding::URL))->isUrlEncoding());
        $this->assertTrue((new Body('x', Encoding::RAW_URL))->isRawUrlEncoding());
        $this->assertTrue((new Body('x', Encoding::BINARY))->isBinaryEncoding());
        $this->assertTrue((new Body('x', Encoding::_7BIT))->is7BitEncoding());
        $this->assertTrue((new Body('x', Encoding::_8BIT))->is8BitEncoding());
    }

    public function testBinaryEncodingRendersContentUnchanged()
    {
        $body = new Body('Hello World!', Encoding::BINARY);
        $this->assertEquals('Hello World!', $body->render());
        $this->assertTrue($body->isEncoded());
    }

    public function testHasContentTrueForPendingFileBeforeAnyAccess()
    {
        $body = new Body();
        $body->setContentFromFile(__DIR__ . '/tmp/test.txt');
        $this->assertTrue($body->hasContent());
        $this->assertTrue($body->isFile());
    }

    public function testFileBackedBase64EncodingMatchesInMemoryEncoding()
    {
        $fileContent = file_get_contents(__DIR__ . '/tmp/test.txt');

        $inMemory = new Body($fileContent, Encoding::BASE64);
        $fromFile = new Body();
        $fromFile->setContentFromFile(__DIR__ . '/tmp/test.txt', Encoding::BASE64);

        $this->assertEquals($inMemory->render(), $fromFile->render());
    }

    public function testFileBackedQuotedPrintableEncodingMatchesInMemoryEncodingForLongContent()
    {
        $longContent = str_repeat('The quick brown fox jumps over the lazy dog. ', 5);
        $tempFile    = tempnam(sys_get_temp_dir(), 'pop-mime-body-test-');
        file_put_contents($tempFile, $longContent);

        $inMemory = new Body($longContent, Encoding::QUOTED_PRINTABLE);
        $fromFile = new Body();
        $fromFile->setContentFromFile($tempFile, Encoding::QUOTED_PRINTABLE);

        $this->assertEquals($inMemory->render(), $fromFile->render());

        unlink($tempFile);
    }

    public function testRenderIsIdempotentForEncodedContent()
    {
        $body = new Body('Hello World!', Encoding::BASE64);
        $first  = $body->render();
        $second = $body->render();
        $this->assertEquals($first, $second);
        $this->assertEquals('SGVsbG8gV29ybGQh', $first);
    }

    public function testRenderIsIdempotentForFileBackedEncodedContent()
    {
        $body = new Body();
        $body->setContentFromFile(__DIR__ . '/tmp/test.txt', Encoding::BASE64);
        $first  = $body->render();
        $second = $body->render();
        $this->assertEquals($first, $second);
    }

    public function testSetContentAfterSetContentFromFileOverridesFile()
    {
        $body = new Body();
        $body->setContentFromFile(__DIR__ . '/tmp/test.txt');
        $body->setContent('Explicit content');
        $this->assertEquals('Explicit content', $body->render());
    }

    public function testSetContentResetsIsEncodedFlag()
    {
        $body = new Body('Hello', Encoding::BASE64);
        $body->render();
        $this->assertTrue($body->isEncoded());
        $body->setContent('World');
        $this->assertFalse($body->isEncoded());
        $this->assertEquals('V29ybGQ=', $body->render());
    }

    public function testToHeaderValue()
    {
        $this->assertEquals('base64', Encoding::BASE64->toHeaderValue());
        $this->assertEquals('quoted-printable', Encoding::QUOTED_PRINTABLE->toHeaderValue());
        $this->assertEquals('binary', Encoding::BINARY->toHeaderValue());
        $this->assertEquals('7bit', Encoding::_7BIT->toHeaderValue());
        $this->assertEquals('8bit', Encoding::_8BIT->toHeaderValue());
        $this->assertNull(Encoding::URL->toHeaderValue());
        $this->assertNull(Encoding::RAW_URL->toHeaderValue());
    }

    public function testFromHeaderValue()
    {
        $this->assertEquals(Encoding::BASE64, Encoding::fromHeaderValue('base64'));
        $this->assertEquals(Encoding::BASE64, Encoding::fromHeaderValue('BASE64'));
        $this->assertEquals(Encoding::QUOTED_PRINTABLE, Encoding::fromHeaderValue('quoted-printable'));
        $this->assertEquals(Encoding::BINARY, Encoding::fromHeaderValue('binary'));
        $this->assertEquals(Encoding::_7BIT, Encoding::fromHeaderValue('7bit'));
        $this->assertEquals(Encoding::_8BIT, Encoding::fromHeaderValue('8bit'));
        $this->assertNull(Encoding::fromHeaderValue('x-uuencode'));
    }

    public function testFileBackedBase64EncodingMatchesInMemoryEncodingForLargeContent()
    {
        $largeContent = random_bytes(100000);
        $tempFile     = tempnam(sys_get_temp_dir(), 'pop-mime-body-test-');
        file_put_contents($tempFile, $largeContent);

        $inMemory = new Body($largeContent, Encoding::BASE64);
        $fromFile = new Body();
        $fromFile->setContentFromFile($tempFile, Encoding::BASE64);

        $this->assertEquals($inMemory->render(), $fromFile->render());

        unlink($tempFile);
    }

}