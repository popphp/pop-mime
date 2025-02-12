pop-mime
========

[![Build Status](https://github.com/popphp/pop-mime/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-mime/actions)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-mime)](http://cc.popphp.org/pop-queue/)

[![Join the chat at https://discord.gg/TZjgT74U7E](https://media.popphp.org/img/discord.svg)](https://discord.gg/TZjgT74U7E)

* [Overview](#overview)
* [Install](#install)
* [Quickstart](#quickstart)
* [Parts](#parts)
  - [Attachments](#attachments)
* [Headers](#headers)
  - [Header Values](#header-values)
  - [Multiple Header Values](#multiple-header-values)
* [Multipart Messages](#multipart-messages)
* [Parsing](#parsing)

Overview
--------
`pop-mime` is a component that provides the ability to work with MIME messages and content. With it, you can
generate properly-formatted MIME messages with all their related headers and parts, or you can parse pre-existing
MIME messages into their respective objects and work with them from there. This can be utilized with mail and HTTP
components, such as `pop-mail` and `pop-http`. 

`pop-mime` is a component of the [Pop PHP Framework](https://www.popphp.org/).

Install
-------

Install `pop-mime` using Composer.

    composer require popphp/pop-mime

Or, require it in your composer.json file

    "require": {
        "popphp/pop-mime" : "^2.0.2"
    }

[Top](#pop-mime)

Quickstart
----------

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

$body = new Body('Hello World!');
$message->setBody($body);

echo $message;
```

This will produce the following MIME message:

```text
Subject: Hello World
To: test@test.com
Date: 11/13/2023 5:38 PM

Hello World!
```

[Top](#pop-mime)

Parts
-----

The main message object is essentially a top-level part object. A part object can contain headers,
a body object or other nested part objects. When a part object has nested parts, this creates a
multipart message. The required boundaries are automatically generated.

```php
use Pop\Mime\Message;
use Pop\Mime\Part;

$message = new Message();
$message->addHeaders([
    'Subject' => 'Hello World',
    'To'      => 'test@test.com',
    'Date'    => date('m/d/Y g:i A')
]);

$message->setSubType('alternative');

$html = new Part();
$html->addHeader('Content-Type', 'text/html');
$html->setBody('<html><body><h1>This is the HTML message.</h1></body></html>');

$text = new Part();
$text->addHeader('Content-Type', 'text/plain');
$text->setBody('This is the text message.');

$message->addParts([$html, $text]);

echo $message;
```

The example above would produce:

```text
Subject: Hello World
To: test@test.com
Date: 10/31/2023 6:12 PM
Content-Type: multipart/alternative; boundary=f79f7366a24132e15132142b0a830a9cac98010f

This is a multi-part message in MIME format.
--f79f7366a24132e15132142b0a830a9cac98010f
Content-Type: text/html

<html><body><h1>This is the HTML message.</h1></body></html>
--f79f7366a24132e15132142b0a830a9cac98010f
Content-Type: text/plain

This is the text message.
--f79f7366a24132e15132142b0a830a9cac98010f--
```

[Top](#pop-mime)

### Attachments

Part objects can be file attachments as well.

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
$file->addHeader('Content-Type', 'application/pdf');
$file->addFile('test.pdf');

$message->addParts([$html, $text, $file]);

echo $message;
```

The example above would produce:

```text
Subject: Hello World
To: test@test.com
Date: 11/13/2023 5:46 PM
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
Content-Type: application/pdf
Content-Disposition: attachment; filename=test.pdf
Content-Transfer-Encoding: base64

JVBERi0xLjQKJcOkw7zDtsOfCjIgMCBvYmoKPDwvTGVuZ3RoIDMgMCBSL0ZpbHRlci9GbGF0ZURl
Y29kZT4+CnN0cmVhbQp4nC3KPQvCQBCE4X5/xdRC4uya3F1gOUhAC7vAgYXY+dEJpvHv5yIyMMXL
[...base64 encoded file contents...]
QzQ2RUUyMDU1RkIxOEY3PiBdCi9Eb2NDaGVja3N1bSAvNUZDMzQxQzBFQzc0MTA2MTZEQzFGRjk4
MDdFMzNFRDgKPj4Kc3RhcnR4cmVmCjc2NDQKJSVFT0YK

--5bedb090b0b35ce8029464dbec97013c3615cc5a--
```

[Top](#pop-mime)

Headers
-------

The header and header value objects allow for easy creation and granular control over the header
values of a MIME message.

```php
$header = new Header('Content-Type', 'text/html');
echo $header;
```

```text
Content-Type: text/html
```

[Top](#pop-mime)

#### Header Values

Header values can be passed into a header object as strings, but they will become header value
objects. When fetching them, you can get the value object like this:

```php
$header = new Header('Content-Type', 'text/html');
print_r($header->getValue()); // Returns an instance of Pop\Mime\Header\Value
```

```text
Pop\Mime\Part\Header\Value Object
(
    [scheme:protected] => 
    [value:protected] => text/html
    [parameters:protected] => Array
        (
        )

    [delimiter:protected] => ;
    [forceQuote:protected] => 
)
```

The benefit of the header value object is that it allows fine-grain control over the header value, including
scheme, parameters, the delimiter and whether or not to force quotes. 

##### Example 1:

```php
use Pop\Mime\Part\Header;

$header = new Header('Content-Disposition');
$value  = new Header\Value('attachment');
$value->addParameter('filename', 'filename.jpg');

$header->addValue($value);
echo $header;
```

```text
Content-Disposition: attachment; filename=filename.jpg
```

##### Example 2:

```php
$header = new Header('Authorization');
$value  = new Header\Value();
$value->setDelimiter(',')
    ->setScheme('Digest ')
    ->setForceQuote(true)
    ->addParameter('username', 'my_username')
    ->addParameter('realm', 'my_realm')
    ->addParameter('nonce', 'my-nonce-123456')
    ->addParameter('uri', '/my-uri')
    ->addParameter('response', 'my-response-123456');

$header->addValue($value);
echo $header;
```

```text
Authorization: Digest username="my_username", realm="my_realm", nonce="my-nonce-123456", uri="/my-uri", response="my-response-123456"
```

You can always get the header value as a string:

```php
$headerString = $header->getValueAsString();
```

[Top](#pop-mime)

#### Multiple Header Values

In some cases, a header may need to contain multiple values. They can be passed as an array to the constructor:

```php
$header = new Header('X-Multi-Header', ['value-1', 'value-2', 'value-3']);
```

or, by individual header value object:

```php
$header = new Header('X-Multi-Header');
$header->addValue('value-1')
    ->addValue('value-2')
    ->addValue('value-3')
echo $header;
```

```text
X-Multi-Header: value-1
X-Multi-Header: value-2
X-Multi-Header: value-3
```

You can access each header value by index:

```php
$value = $header->getValue(2);
```

[Top](#pop-mime)

Multipart Messages
------------------

There is a interface to assist in easily creating multipart messages, instead of doing it the more manual way
outlined in the above examples.

#### HTTP Multipart Form

```php
use Pop\Mime\Message;

$formData = [
    'username' => 'admin@test/whatever%DUDE!',
    'password' => '123456',
    'colors'   => ['Red', 'Green']
];

$formMessage = Message::createForm($formData);
echo $formMessage;
```

```text
Content-Type: multipart/form-data; boundary=1f39a2798e049befa5b835a1434a6c7a21e9713a

This is a multi-part message in MIME format.
--1f39a2798e049befa5b835a1434a6c7a21e9713a
Content-Disposition: form-data; name=username

admin%40test%2Fwhatever%25DUDE%21
--1f39a2798e049befa5b835a1434a6c7a21e9713a
Content-Disposition: form-data; name=password

123456
--1f39a2798e049befa5b835a1434a6c7a21e9713a
Content-Disposition: form-data; name=colors[]

Red
--1f39a2798e049befa5b835a1434a6c7a21e9713a
Content-Disposition: form-data; name=colors[]

Green
--1f39a2798e049befa5b835a1434a6c7a21e9713a--
```

If you just need the main form parts without the top-level header and MIME preamble,
you can do that like this:

```php
use Pop\Mime\Message;

$formData = [
    'username' => 'admin@test/whatever%DUDE!',
    'password' => '123456',
    'colors'   => ['Red', 'Green']
];

$formMessage = Message::createForm($formData);
echo $formMessage->renderRaw();
```

And that will render just the form data content, removing the top-level header
and the preamble:

```text
--28fd350696733cf5d2c466383a7e0193a5cfffc3
Content-Disposition: form-data; name=username

admin%40test%2Fwhatever%25DUDE%21
--28fd350696733cf5d2c466383a7e0193a5cfffc3
Content-Disposition: form-data; name=password

123456
--28fd350696733cf5d2c466383a7e0193a5cfffc3
Content-Disposition: form-data; name=colors[]

Red
--28fd350696733cf5d2c466383a7e0193a5cfffc3
Content-Disposition: form-data; name=colors[]

Green
--28fd350696733cf5d2c466383a7e0193a5cfffc3--
```

#### HTTP Multipart Form with a File

You can also create form data with files in a couple of different ways as well:

*Example 1:*

```php
$formData = [
    'file'     => [
        'filename'    => __DIR__ . '/test.pdf',
        'contentType' => 'application/pdf'
    ]
];
```

*Example 2:*

```php
$formData = [
    'file'     => [
        'filename' => 'test.pdf',
        'contents' => file_get_contents(__DIR__ . '/test.pdf')
        'mimeType' => 'application/pdf'
    ]
];
```

In example 1, the file on disk is passed and put into the form data from there.
In example 2, the file contents are explicitly passed to the `contents` key to
set the file data into the form data. Also, for flexibility, the following
case-insensitive keys are acceptable for `Content-Type`:

- Content-Type
- contentType
- Mime-Type
- mimeType
- mime

[Top](#pop-mime)

Parsing
-------

#### *Note:*

*This component adheres to the MIME standard which uses CRLF ("\r\n") for line breaks.
If a mime message does not adhere to this standard, parsing may not work as intended.*

**Parsing a message:**

To parse MIME messages and content, you can take the string of MIME message content
and pass it in the following method and it will return a message object with
all of the related headers and parts.

```php
use Pop\Mime\Message;

$message = Message::parseMessage($messageString);
```

**Parsing a header string:**

If you happen to have the MIME header string, you can parse just that like below.
This will return an array of header objects:

```php
use Pop\Mime\Message;

$headers = Message::parseMessage($headerString);
```

**Parsing a body string:**

If you happen to have the MIME body string, you can parse just that like below.
This will return an array of part objects:

```php
use Pop\Mime\Message;

$parts = Message::parseBody($bodyString);
```

**Parsing a single part string:**

And if you happen to have the string of a single MIME part, you can parse just
that like below. This will return a part object:

```php
use Pop\Mime\Message;

$part = Message::parsePart($partString);
```

**Parsing form data:**

As a special case, if you have `multipart/form-data` MIME content, you can parse
it like below. This will return a form data array:

```php
use Pop\Mime\Message;

$formData = Message::parseForm($formString);
```

It's important to note that in order for the above example to work properly, it
has to have a header with at least the `Content-Type` defined, including the boundary
that will be used in parsing the form data:

```text
Content-Type: multipart/form-data;
    boundary=5bedb090b0b35ce8029464dbec97013c3615cc5a

--5bedb090b0b35ce8029464dbec97013c3615cc5a
Content-Disposition: form-data; name="username"

admin
--5bedb090b0b35ce8029464dbec97013c3615cc5a
Content-Disposition: form-data; name="password"

password
--5bedb090b0b35ce8029464dbec97013c3615cc5a--
```

[Top](#pop-mime)
