pop-mime
========

[![Build Status](https://travis-ci.org/popphp/pop-mime.svg?branch=master)](https://travis-ci.org/popphp/pop-mime)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-mime)](http://cc.popphp.org/pop-queue/)

OVERVIEW
--------
`pop-mime` is a component that provides the ability to work with MIME messages and content. With it, you can
generate properly-formatted MIME messages with all their related headers and parts, or you can parse pre-existing
MIME messages into their respective objects and work with them from there. This can be utilized with mail and HTTP
components, such as `pop-mail` and `pop-http`. 

`pop-mime` is a component of the [Pop PHP Framework](http://www.popphp.org/).

INSTALL
-------

Install `pop-mime` using Composer.

    composer require popphp/pop-mime

BASIC USAGE
-----------

### Creating a Simple MIME Message:

```php
use Pop\Mime\Message;
use Pop\Mime\Part\Body;

$message = new Message();
$message->addHeaders([
    'Subject' => 'Hello World',
    'To'      => 'test@test.com',
    'Date'    => date('m/d/Y g:i A')
]);

$body = new Body("Hello World!");
$message->setBody($body);

echo $message;

```

This will produce the following MIME message:

```text
Subject: Hello World
To: test@test.com
Date: 11/13/2019 5:38 PM

Hello World!

```

### Complex Headers:

The header object allows you to create complex MIME headers with supporting parameters.
You can also control things like the wrap of longer headers with multiple lines
and indentation of those headers:

```php
use Pop\Mime\Part\Header;

$header = new Header('Content-Disposition', 'form-data');
$header->addParameter('name', 'image')
    ->addParameter('filename', '/tmp/some image.jpg')
    ->addParameter('foo', 'Some other param')
    ->addParameter('bar', 'another')
    ->addParameter('baz', 'one more parameter');

$headerParsed->setWrap(76)
    ->setIndent("\t");

echo $header;
```

The above header will look like:

```text
Content-Disposition: form-data; name=image; filename="some image.jpg";
	foo=bar; bar=another; baz="one more parameter"
``` 

### Multi-part MIME Message

Below is an example of a text and HTML multi-part MIME message:

```php
use Pop\Mime\Message;
use Pop\Mime\Part;

$message = new Message();
$message->addHeaders([
    'Subject'      => 'Hello World',
    'To'           => 'test@test.com',
    'Date'         => date('m/d/Y g:i A'),
    'MIME-Version' => '1.0'
]);

$message->setSubType('alternative');

$html = new Part();
$html->addHeader('Content-Type', 'text/html');
$html->setBody(
    '<html><body><h1>This is the HTML message.</h1></body></html>'
);

$text = new Part();
$text->addHeader('Content-Type', 'text/plain');
$text->setBody('This is the text message.');

$message->addParts([$html, $text]);

echo $message;
```

```text
Subject: Hello World
To: test@test.com
Date: 11/13/2019 5:44 PM
MIME-Version: 1.0
Content-Type: multipart/alternative;
	boundary=f86344638714cf8a0c8e7bcf89b8fd10552b921a

This is a multi-part message in MIME format.
--f86344638714cf8a0c8e7bcf89b8fd10552b921a
Content-Type: text/html

<html><body><h1>This is the HTML message.</h1></body></html>
--f86344638714cf8a0c8e7bcf89b8fd10552b921a
Content-Type: text/plain

This is the text message.
--f86344638714cf8a0c8e7bcf89b8fd10552b921a--
```

### Multi-part MIME Message with an Attachment

```php
use Pop\Mime\Message;
use Pop\Mime\Part;

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
$html->setBody('<html><body><h1>This is the HTML message.</h1></body></html>');

$text = new Part();
$text->addHeader('Content-Type', 'text/plain');
$text->setBody('This is the text message.');

$file = new Part();
$file->addHeader('Content-Type', 'application/octet-stream');
$file->addFile('test.pdf');

$message->addParts([$html, $text, $file]);

echo $message;
```

The above message will produce the following:

```text
Subject: Hello World
To: test@test.com
Date: 11/13/2019 5:46 PM
MIME-Version: 1.0
Content-Type: multipart/mixed;
	boundary=5bedb090b0b35ce8029464dbec97013c3615cc5a

This is a multi-part message in MIME format.
--5bedb090b0b35ce8029464dbec97013c3615cc5a
Content-Type: text/html

<html><body><h1>This is the HTML message.</h1></body></html>
--5bedb090b0b35ce8029464dbec97013c3615cc5a
Content-Type: text/plain

This is the text message.
--5bedb090b0b35ce8029464dbec97013c3615cc5a
Content-Type: application/octet-stream
Content-Disposition: attachment; filename=test.pdf
Content-Transfer-Encoding: base64

JVBERi0xLjQKJcOkw7zDtsOfCjIgMCBvYmoKPDwvTGVuZ3RoIDMgMCBSL0ZpbHRlci9GbGF0ZURl
Y29kZT4+CnN0cmVhbQp4nC3KPQvCQBCE4X5/xdRC4uya3F1gOUhAC7vAgYXY+dEJpvHv5yIyMMXL

[...base64 encoded file contents...]

QzQ2RUUyMDU1RkIxOEY3PiBdCi9Eb2NDaGVja3N1bSAvNUZDMzQxQzBFQzc0MTA2MTZEQzFGRjk4
MDdFMzNFRDgKPj4Kc3RhcnR4cmVmCjc2NDQKJSVFT0YK

--5bedb090b0b35ce8029464dbec97013c3615cc5a--

```

### Parsing MIME Messages

To parse MIME messages and content, you can take the string of MIME message content
and pass it in the following method and it will return a message object with
all of the related headers and parts.

```php
use Pop\Mime\Message;

$message = Message::parseMessage($messageString);
```

If you happen to have the MIME header string, you can parse just that like below.
This will return an array of header objects:

```php
use Pop\Mime\Message;

$headers = Message::parseMessage($headerString);
```

If you happen to have the MIME body string, you can parse just that like below.
This will return an array of part objects:

```php
use Pop\Mime\Message;

$parts = Message::parseBody($bodyString);
```

And if you happen to have the string of a single MIME part, you can parse just
that like below. This will return a part object:

```php
use Pop\Mime\Message;

$part = Message::parsePart($partString);
```
