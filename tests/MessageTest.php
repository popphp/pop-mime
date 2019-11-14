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

        $header = new Header('Content-Disposition', 'form-data');
        $header->addParameter('name', 'image')
            ->addParameter('filename', '/tmp/some image.jpg')
            ->addParameter('foo', 'Some other param')
            ->addParameter('bar', 'another')
            ->addParameter('baz', 'one more parameter');

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
        $this->assertEquals('Nick', $formData['first_name']);
        $this->assertEquals('Sagona', $formData['last_name']);
        $this->assertEquals('test@test.com', $formData['email']);
        $this->assertTrue(is_array($formData['colors']));
        $this->assertEquals(2, count($formData['colors']));
        $this->assertEquals('Red', $formData['colors'][0]);
        $this->assertEquals('Green', $formData['colors'][1]);
    }

    public function testParseMessageWithFile()
    {
        $message = new Message();
        $message->addHeaders([
            'Subject'      => 'Hello World',
            'To'           => 'nicks3123@gmail.com',
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
        $this->assertContains($boundary, $originalMessageString);

        $parsedMessage = Message::parseMessage($originalMessageString);

        $this->assertTrue($parsedMessage->hasAttachments());
        $attachments = $parsedMessage->getAttachments();
        $this->assertEquals(1, count($attachments));
        $this->assertEquals('application/octet-stream', $attachments[0]->getContentType());
        $this->assertEquals('test.pdf', $attachments[0]->getFilename());
        $this->assertContains('PDF', $attachments[0]->getContents());

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

        $this->assertContains('Hello World', $part->getContents());
        $this->assertInstanceOf('Pop\Mime\Part', $parsedPart);
    }

}