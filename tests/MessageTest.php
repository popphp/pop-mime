<?php

namespace Pop\Mime\Test;

use Pop\Mime\Message;
use Pop\Mime\Part;
use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{

    public function testConstructor()
    {
        $message = new Message();
        $this->assertInstanceOf('Pop\Mime\Message', $message);
    }

    public function testParseMessage()
    {
        $message = new Message();
        $message->addHeaders([
            'Subject'      => 'Hello World',
            'To'           => 'test@test.com',
            'Date'         => date('m/d/Y g:i A')
        ]);

        $headerValue = new Header\Value('form-data');
        $headerValue->addParameter('name', 'image')
            ->addParameter('filename', '/tmp/some image.jpg')
            ->addParameter('foo', 'Some other param')
            ->addParameter('bar', 'another')
            ->addParameter('baz', 'one more parameter');

        $header = new Header('Content-Disposition', $headerValue);

        $header->setWrap(76)
            ->setIndent("\t");

        $message->addHeader($header);
        $message->addHeader('MIME-Version', '1.0');

        $message->setSubType('alternative');

        $html = new Part();
        $html->addHeader('Content-Type', 'text/html');
        $html->setBody('<html><body><h1>This is the text message.</h1></body></html>');

        $text = new Part();
        $text->addHeader('Content-Type', 'text/plain');
        $text->setBody('This is the text message.');

        $message->addParts([$html, $text]);

        $originalMessageString = $message->render();

        $parsedMessage = Message::parseMessage($originalMessageString);
        $this->assertFalse($parsedMessage->hasAttachments());

        $this->assertEquals(6, count($message->getHeaders()));
        $this->assertEquals(6, count($parsedMessage->getHeaders()));
    }

    public function testParseForm()
    {
        $formData = Message::parseForm(file_get_contents(__DIR__ . '/tmp/form-file.txt'));
        $this->assertEquals('admin@something%test+what/ever', $formData['username']);
        $this->assertEquals('234234', $formData['password']);
        $this->assertEquals('John', $formData['first_name']);
        $this->assertEquals('Doe', $formData['last_name']);
        $this->assertEquals('test@test.com', $formData['email']);
        $this->assertTrue(is_array($formData['colors']));
        $this->assertEquals(2, count($formData['colors']));
        $this->assertEquals('Red', $formData['colors'][0]);
        $this->assertEquals('Green', $formData['colors'][1]);
    }

    public function testCreateForm1()
    {
        $formData = [
            'username' => 'admin@test/whatever%DUDE!',
            'password' => '123456',
            'colors'   => ['Red', 'Green'],
            'file'     => [
                'filename'    => __DIR__ . '/tmp/test.pdf',
                'contentType' => 'application/pdf'
            ]
        ];

        $formMessage = Message::createForm($formData);
        $contents    = $formMessage->render(false);

        $this->assertStringContainsString('Content-Type: multipart/form-data; boundary=', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=username', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=password', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=colors[]', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=file; filename=test.pdf', $contents);
        $this->assertStringContainsString('Content-Type: application/pdf', $contents);
        $this->assertStringContainsString('admin%40test%2Fwhatever%25DUDE%21', $contents);
        $this->assertStringContainsString('123456', $contents);
        $this->assertStringContainsString('Red', $contents);
        $this->assertStringContainsString('Green', $contents);
        $this->assertStringContainsString('%PDF-1.4', $contents);
    }

    public function testCreateForm2()
    {
        $formData = [
            'username' => 'admin@test/whatever%DUDE!',
            'password' => '123456',
            'colors'   => ['Red', 'Green'],
            'file'     => [
                'filename'    => __DIR__ . '/tmp/test.pdf',
                'contentType' => 'application/pdf'
            ]
        ];

        $formMessage = Message::createForm($formData);
        $contents    = $formMessage->renderRaw();

        $this->assertStringNotContainsString('Content-Type: multipart/form-data; boundary=', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=username', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=password', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=colors[]', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=file; filename=test.pdf', $contents);
        $this->assertStringContainsString('Content-Type: application/pdf', $contents);
        $this->assertStringContainsString('admin%40test%2Fwhatever%25DUDE%21', $contents);
        $this->assertStringContainsString('123456', $contents);
        $this->assertStringContainsString('Red', $contents);
        $this->assertStringContainsString('Green', $contents);
        $this->assertStringContainsString('%PDF-1.4', $contents);
    }

    public function testCreateFormWithFileContents()
    {
        $formData = [
            'username' => 'admin@test/whatever%DUDE!',
            'password' => '123456',
            'colors'   => ['Red', 'Green'],
            'file'     => [
                'filename'    => 'test.pdf',
                'contents'    => file_get_contents(__DIR__ . '/tmp/test.pdf'),
                'contentType' => 'application/pdf'
            ]
        ];

        $formMessage = Message::createForm($formData);
        $contents    = $formMessage->render(false);

        $this->assertStringContainsString('Content-Type: multipart/form-data; boundary=', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=username', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=password', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=colors[]', $contents);
        $this->assertStringContainsString('Content-Disposition: form-data; name=file; filename=test.pdf', $contents);
        $this->assertStringContainsString('Content-Type: application/pdf', $contents);
        $this->assertStringContainsString('admin%40test%2Fwhatever%25DUDE%21', $contents);
        $this->assertStringContainsString('123456', $contents);
        $this->assertStringContainsString('Red', $contents);
        $this->assertStringContainsString('Green', $contents);
        $this->assertStringContainsString('%PDF-1.4', $contents);
    }

    public function testParseMessageWithFile()
    {
        $message = new Message();
        $message->addHeaders([
            'Subject'      => 'Hello World',
            'To'           => 'test@test.com',
            'Date'         => date('m/d/Y g:i A'),
            'MIME-Version' => '1.0'
        ]);

        $message->setSubType('mixed');

        $this->assertEquals('mixed', $message->getSubType());

        $html = new Part();
        $html->addHeader('Content-Type', 'text/html');
        $html->setBody('<html><body><h1>This is the text message.</h1></body></html>');

        $text = new Part();
        $text->addHeader('Content-Type', 'text/plain');
        $text->setBody('This is the text message.');

        $file = new Part();
        $file->addHeader('Content-Type', 'application/octet-stream');
        $file->addFile(__DIR__ . '/tmp/test.pdf');

        $message->addParts([$html, $text, $file]);

        $originalMessageString = $message->render();

        $boundary = $message->getBoundary();
        $this->assertNotEmpty($boundary);
        $this->assertStringContainsString($boundary, $originalMessageString);

        $parsedMessage = Message::parseMessage($originalMessageString);

        $this->assertTrue($parsedMessage->hasAttachments());
        $attachments = $parsedMessage->getAttachments();
        $this->assertEquals(1, count($attachments));
        $this->assertEquals('application/octet-stream', $attachments[0]->getContentType());
        $this->assertEquals('test.pdf', $attachments[0]->getFilename());
        $this->assertStringContainsString('PDF', $attachments[0]->getContents());

        $this->assertEquals(5, count($message->getHeaders()));
        $this->assertEquals(5, count($parsedMessage->getHeaders()));
    }

    public function testParseBody()
    {
        $parts = Message::parseBody("Hello World! What's up?!");
        $this->assertEquals(1, count($parts));
        $this->assertEquals("Hello World! What's up?!", $parts[0]);
    }

    public function testParsePartBody()
    {
        $part = Message::parsePart("Hello World! What's up?!");
        $this->assertInstanceOf('Pop\Mime\Part', $part);
    }

    public function testParsePartQuoted()
    {
        $body = new Body(
            "Hello World! What's up?! Hello World! What's up?! Hello World! What's up?! Hello World! What's up?! Hello World! What's up?!"
        );
        $body->setEncoding(Body::QUOTED);

        $part = new Part();
        $part->setBody($body);

        $parsedPart = Message::parsePart($part->render());

        $this->assertStringContainsString('Hello World', $part->getContents());
        $this->assertInstanceOf('Pop\Mime\Part', $parsedPart);
    }

    public function testParsePartWithSubParts1()
    {
        $message = new Message();
        $message->addHeaders([
            'Subject'      => 'Hello World',
            'To'           => 'test@test.com',
            'Date'         => date('m/d/Y g:i A'),
            'MIME-Version' => '1.0'
        ]);

        $message->setSubType('mixed');

        $html = new Part();
        $html->addHeader('Content-Type', 'text/html');
        $html->setBody('<html><body><h1>This is the text message.</h1></body></html>');

        $text = new Part();
        $text->addHeader('Content-Type', 'text/plain');
        $text->setBody('This is the text message.');

        $part = new Part();
        $part->setSubType('alternative');

        $part->addParts([$html, $text]);

        $file = new Part();
        $file->addHeader('Content-Type', 'application/octet-stream');
        $file->addFile(__DIR__ . '/tmp/test.pdf');

        $message->addParts([$part, $file]);

        $originalMessageString = $message->render();

        $message = Message::parseMessage($originalMessageString);

        $this->assertEquals(5, count($message->getHeaders()));
        $this->assertEquals(2, count($message->getParts()));
        $this->assertEquals(2, count($message->getParts()[0]->getParts()));
    }

    public function testParsePartWithSubParts2()
    {
        $message = new Message();
        $message->addHeaders([
            'Subject'      => 'Hello World',
            'To'           => 'test@test.com',
            'Date'         => date('m/d/Y g:i A'),
            'MIME-Version' => '1.0'
        ]);

        $message->setSubType('mixed');

        $html = new Part();
        $html->addHeader('Content-Type', 'text/html');
        $html->setBody('<html><body><h1>This is the text message.</h1></body></html>');

        $text = new Part();
        $text->addHeader('Content-Type', 'text/plain');
        $text->setBody('This is the text message.');

        $file = new Part();
        $file->addHeader('Content-Type', 'application/octet-stream');
        $file->addFile(__DIR__ . '/tmp/test.pdf');

        $part = new Part();
        $part->setSubType('alternative');

        $part->addParts([$html, $text, $file]);

        $message->addPart($part);

        $originalMessageString = $message->render();

        $message = Message::parseMessage($originalMessageString);

        $this->assertEquals(5, count($message->getHeaders()));
        $this->assertEquals(1, count($message->getParts()));
        $this->assertEquals(3, count($message->getParts()[0]->getParts()));
    }

}
