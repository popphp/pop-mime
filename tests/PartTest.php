<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part;
use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;
use Pop\Mime\Part\Body\Encoding;
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
        $body = new Body('admin@something%testwhat/ever', Encoding::URL);
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
        $header = new Header('Content-Disposition');
        $value  = new Header\Value('attachment', null, ['filename' => '=?UTF-8?B?SGVsbG8=?=.txt']);
        $header->addValue($value);

        $part = new Part($header);
        $part->setBody(new Body(file_get_contents(__DIR__ . '/tmp/test.txt')));
        $part->getBody()->setAsFile(true);
        $this->assertEquals('Hello.txt', $part->getFilename());
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

    public function testBinaryEncodingAddsContentTransferEncodingHeader()
    {
        $part = new Part();
        $part->setBody(new Body('Hello World!', Encoding::BINARY));
        $rendered = $part->render();
        $this->assertStringContainsString('Content-Transfer-Encoding: binary', $rendered);
    }

    public function test7BitEncodingAddsContentTransferEncodingHeader()
    {
        $part = new Part();
        $part->setBody(new Body('Hello World!', Encoding::_7BIT));
        $rendered = $part->render();
        $this->assertStringContainsString('Content-Transfer-Encoding: 7bit', $rendered);
    }

    public function test8BitEncodingAddsContentTransferEncodingHeader()
    {
        $part = new Part();
        $part->setBody(new Body('Hello World!', Encoding::_8BIT));
        $rendered = $part->render();
        $this->assertStringContainsString('Content-Transfer-Encoding: 8bit', $rendered);
    }

    public function testGetContentsWithBinaryEncodingReturnsContentUnchanged()
    {
        $part = new Part();
        $body = new Body('Hello World!', Encoding::BINARY);
        $part->setBody($body);
        $part->render();
        $this->assertEquals('Hello World!', $part->getContents());
    }

    public function testGetFilenameWithUnrecognizedCharsetDegradesGracefully()
    {
        $header = new Header('Content-Disposition');
        $value  = new Header\Value('attachment', null, ['filename' => '=?unknown-8bit?B?QQ==?=.txt']);
        $header->addValue($value);

        $part = new Part($header);
        $part->setBody(new Body(file_get_contents(__DIR__ . '/tmp/test.txt')));
        $part->getBody()->setAsFile(true);
        $this->assertIsString($part->getFilename());
    }

    public function testGetContentTypeReturnsNullWhenNoHeaderSet()
    {
        $part = new Part();
        $this->assertNull($part->getContentType());
    }

    public function testGenerateId()
    {
        $part = new Part();
        $id   = $part->generateId('example.com');
        $this->assertMatchesRegularExpression('/^<[a-f0-9]{32}@example\.com>$/', $id);
    }

    public function testGenerateIdFallsBackToLocalhost()
    {
        $originalServerName = $_SERVER['SERVER_NAME'] ?? null;
        unset($_SERVER['SERVER_NAME']);

        $part = new Part();
        $id   = $part->generateId();
        $this->assertStringEndsWith('@localhost>', $id);

        if ($originalServerName !== null) {
            $_SERVER['SERVER_NAME'] = $originalServerName;
        }
    }

    public function testSetContentIdWithExplicitId()
    {
        $part = new Part();
        $part->setContentId('<abc123@example.com>');
        $this->assertTrue($part->hasHeader('Content-ID'));
        $this->assertEquals('<abc123@example.com>', (string)$part->getHeader('Content-ID')->getValue(0));
    }

    public function testSetContentIdGeneratesWhenOmitted()
    {
        $part = new Part();
        $part->setContentId(null, 'example.com');
        $this->assertTrue($part->hasHeader('Content-ID'));
        $this->assertMatchesRegularExpression('/^<[a-f0-9]{32}@example\.com>$/', (string)$part->getHeader('Content-ID')->getValue(0));
    }

    public function testTextFactory()
    {
        $part = Part::text('Hello World!');
        $this->assertTrue($part->hasHeader('Content-Type'));
        $this->assertEquals('text/plain', (string)$part->getHeader('Content-Type')->getValue(0));
        $this->assertEquals('Hello World!', $part->getContents());
    }

    public function testHtmlFactory()
    {
        $part = Part::html('<p>Hello</p>');
        $this->assertTrue($part->hasHeader('Content-Type'));
        $this->assertEquals('text/html', (string)$part->getHeader('Content-Type')->getValue(0));
        $this->assertEquals('<p>Hello</p>', $part->getContents());
    }

    public function testAttachmentFactoryDetectsContentTypeFromExtension()
    {
        $part = Part::attachment(__DIR__ . '/tmp/test.txt');
        $this->assertEquals('text/plain', (string)$part->getHeader('Content-Type')->getValue(0));
        $this->assertTrue($part->hasHeader('Content-Disposition'));
        $this->assertEquals('Hello World!', trim($part->getContents()));
    }

    public function testAttachmentFactoryDetectsPdfContentType()
    {
        $part = Part::attachment(__DIR__ . '/tmp/test.pdf');
        $this->assertEquals('application/pdf', (string)$part->getHeader('Content-Type')->getValue(0));
    }

    public function testAttachmentFactoryFallsBackToOctetStreamForUnknownExtension()
    {
        $part = Part::attachment(__DIR__ . '/tmp/test.unknownext');
        $this->assertEquals('application/octet-stream', (string)$part->getHeader('Content-Type')->getValue(0));
    }

    public function testAttachmentFactoryDetectsMarkdownAsTextPlain()
    {
        $part = Part::attachment(__DIR__ . '/tmp/test.md');
        $this->assertEquals('text/plain', (string)$part->getHeader('Content-Type')->getValue(0));
    }

    public function testAttachmentFactoryDetectsZipContentTypeViaPopUtilsFile()
    {
        $part = Part::attachment(__DIR__ . '/tmp/test.zip');
        $this->assertEquals('application/zip', (string)$part->getHeader('Content-Type')->getValue(0));
    }

    public function testAttachmentFactoryRespectsExplicitContentType()
    {
        $part = Part::attachment(__DIR__ . '/tmp/test.txt', 'application/x-custom');
        $this->assertEquals('application/x-custom', (string)$part->getHeader('Content-Type')->getValue(0));
    }

    public function testAttachmentFromContentFactory()
    {
        $part = Part::attachmentFromContent('raw file contents', 'generated.txt');
        $this->assertEquals('text/plain', (string)$part->getHeader('Content-Type')->getValue(0));
        $this->assertTrue($part->hasHeader('Content-Disposition'));
        $this->assertEquals('generated.txt', $part->getHeader('Content-Disposition')->getValue(0)->getParameter('filename'));
        $this->assertTrue($part->getBody()->isFile());
        $this->assertEquals('raw file contents', $part->getContents());
    }

    public function testInferSubTypeAlternativeForTextAndHtml()
    {
        $part = new Part();
        $part->addPart(Part::text('Hello'));
        $part->addPart(Part::html('<p>Hello</p>'));
        $part->inferSubType();
        $this->assertEquals('alternative', $part->getSubType());
    }

    public function testInferSubTypeMixedForFileAttachment()
    {
        $part = new Part();
        $part->addPart(Part::text('Hello'));
        $part->addPart(Part::attachment(__DIR__ . '/tmp/test.txt'));
        $part->inferSubType();
        $this->assertEquals('mixed', $part->getSubType());
    }

    public function testInferSubTypeMixedWhenAttachmentContentTypeCouldBeMistakenForText()
    {
        $part = new Part();
        $part->addPart(Part::html('<p>Hello</p>'));
        $part->addPart(Part::attachment(__DIR__ . '/tmp/test.txt'));
        $part->inferSubType();
        $this->assertEquals('mixed', $part->getSubType());
    }

    public function testInferSubTypeFileWinsOverTextAndHtml()
    {
        $part = new Part();
        $part->addPart(Part::text('Hello'));
        $part->addPart(Part::html('<p>Hello</p>'));
        $part->addPart(Part::attachment(__DIR__ . '/tmp/test.pdf'));
        $part->inferSubType();
        $this->assertEquals('mixed', $part->getSubType());
    }

    public function testGetSubTypeReturnsNullWhenNotSet()
    {
        $part = new Part();
        $this->assertNull($part->getSubType());
    }

    public function testInferSubTypeLeavesSubTypeUnsetWhenNoMatch()
    {
        $part = new Part();
        $part->addPart(Part::text('Hello'));
        $part->inferSubType();
        $this->assertFalse($part->hasSubType());
    }

    public function testInferSubTypeDoesNotCrashOnPartWithNoContentTypeHeader()
    {
        $bare = new Part();
        $bare->setBody('no content-type header here');

        $part = new Part();
        $part->addPart($bare);
        $part->addPart(Part::attachment(__DIR__ . '/tmp/test.txt'));
        $part->inferSubType();
        $this->assertEquals('mixed', $part->getSubType());
    }

    public function testInferSubTypeComposesWithRenderParts()
    {
        $part = new Part();
        $part->addPart(Part::text('Hello'));
        $part->addPart(Part::html('<p>Hello</p>'));
        $part->inferSubType();

        $rendered = $part->render();
        $this->assertStringContainsString('Content-Type: multipart/alternative; boundary=', $rendered);
        $this->assertStringContainsString('This is a multi-part message in MIME format.', $rendered);
    }

    public function testFactoriesUseLateStaticBindingForSubclasses()
    {
        $text       = PartSubclassFixture::text('Hello');
        $html       = PartSubclassFixture::html('<p>Hello</p>');
        $attachment = PartSubclassFixture::attachment(__DIR__ . '/tmp/test.txt');
        $fromMemory = PartSubclassFixture::attachmentFromContent('raw content', 'generated.txt');

        $this->assertInstanceOf(PartSubclassFixture::class, $text);
        $this->assertInstanceOf(PartSubclassFixture::class, $html);
        $this->assertInstanceOf(PartSubclassFixture::class, $attachment);
        $this->assertInstanceOf(PartSubclassFixture::class, $fromMemory);
    }

}

class PartSubclassFixture extends Part
{
}