pop-mime
========

[![Build Status](https://github.com/popphp/pop-mime/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-mime/actions)
[![Coverage Status](https://cc.popphp.org/coverage.php?comp=pop-mime)](https://cc.popphp.org/pop-mime/)

[![Join the chat at https://discord.gg/TZjgT74U7E](https://media.popphp.org/img/discord.svg)](https://discord.gg/TZjgT74U7E)

* [Overview](#overview)
* [Install](#install)
* [Quickstart](#quickstart)
* [Parts](#parts)
  - [Part Factories](#part-factories)
  - [Attachments](#attachments)
  - [Encoding](#encoding)
* [Headers](#headers)
  - [Header Values](#header-values)
  - [Multiple Header Values](#multiple-header-values)
  - [Addresses](#addresses)
  - [Non-ASCII Header Values](#non-ascii-header-values)
* [Message and Content IDs](#message-and-content-ids)
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
        "popphp/pop-mime" : "^3.0.0"
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

### Part Factories

Building simple `text/plain` and `text/html` parts by hand — creating a `Part`, adding a `Content-Type`
header, then setting the body — is common enough to have shortcuts. `Part::text()` and `Part::html()` do
all three in one call:

```php
use Pop\Mime\Message;
use Pop\Mime\Part;

$message = new Message();
$message->addHeaders([
    'Subject' => 'Hello World',
    'To'      => 'test@test.com',
    'Date'    => date('m/d/Y g:i A')
]);

$message->addParts([
    Part::html('<html><body><h1>This is the HTML message.</h1></body></html>'),
    Part::text('This is the text message.')
]);

$message->inferSubType();

echo $message;
```

`inferSubType()` decides the multipart subtype for you instead of calling `setSubType()` by hand: if the
message has both a `text/plain` and a `text/html` part, it sets `alternative`; if any part is a file
attachment, it sets `mixed` (a file always wins, even alongside a text/html pair, so an attachment can
never be silently dropped by a mail client that only renders one part of an `alternative` message). It's
opt-in — nothing calls it automatically — so it's safe to keep using `setSubType()` directly if you want
full manual control.

This produces the same kind of output as the manual example above (with a freshly-generated boundary each
run).

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

The `$file` part above can be built in one call instead, using `Part::attachment()`. It auto-detects the
`Content-Type` from the file's extension (falling back to `application/octet-stream` for an unrecognized
one), so the manual `addHeader('Content-Type', ...)` call isn't needed:

```php
$file = Part::attachment('test.pdf');
```

You can still pass an explicit content type as the second argument if you don't want auto-detection:

```php
$file = Part::attachment('test.pdf', 'application/pdf');
```

If you have file content in memory rather than an actual file on disk, `Part::attachmentFromContent()`
does the same thing without requiring a real file path:

```php
$file = Part::attachmentFromContent($pdfBytes, 'test.pdf');
```

[Top](#pop-mime)

### Encoding

`addFile()` and the attachment factories above default to base64 encoding, but any part's body can use
`Pop\Mime\Part\Body\Encoding`, a backed enum with these cases:

| Case               | Wire value          |
|--------------------|----------------------|
| `BASE64`           | `base64`             |
| `QUOTED_PRINTABLE` | `quoted-printable`   |
| `BINARY`           | `binary`             |
| `_7BIT`            | `7bit`                |
| `_8BIT`            | `8bit`                |
| `URL`              | `URL`                 |
| `RAW_URL`          | `RAW_URL`             |

Pass it as the third argument to `addFile()`, or the fourth to `Part::attachment()`/`attachmentFromContent()`:

```php
use Pop\Mime\Part\Body\Encoding;

$file = Part::attachment('test.pdf', null, 'attachment', Encoding::QUOTED_PRINTABLE);
```

A part's body can also be constructed directly with an encoding, independent of the attachment factories:

```php
use Pop\Mime\Part\Body;

$body = new Body('Hello World!', Encoding::QUOTED_PRINTABLE);
```

When a part's body has an encoding set, a matching `Content-Transfer-Encoding` header is added
automatically on render, and `getContents()` decodes it back to the original content.

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
    ->addValue('value-3');
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

#### Addresses

Address headers like `To`, `From`, `Cc`, `Bcc` and `Reply-To` get their own value type,
`Pop\Mime\Part\Header\AddressList` (a collection of `Pop\Mime\Part\Header\Address` objects), built on top
of the same header/value machinery above. It correctly handles a display name that itself contains a
comma — something naive `explode(',', ...)` splitting gets wrong — because it tokenizes the whole header
value instead of blindly splitting on every comma:

```php
use Pop\Mime\Part\Header\AddressList;

$list = AddressList::parse('"Doe, John" <john@doe.com>, Jane Doe <jane@doe.com>');

foreach ($list->getAddresses() as $address) {
    echo $address->getName() . ' <' . $address->getAddress() . ">\n";
}
```

```text
Doe, John <john@doe.com>
Jane Doe <jane@doe.com>
```

Two addresses, not three — the comma inside the quoted `"Doe, John"` display name is not treated as a
separator. `render()` (or a cast to `string`) turns the list back into a single header-ready value, adding
quotes only where the display name actually needs them:

```php
echo $list->render();
```

```text
"Doe, John" <john@doe.com>, Jane Doe <jane@doe.com>
```

You can build one up directly instead of parsing a string, and wire it straight into a header:

```php
use Pop\Mime\Message;
use Pop\Mime\Part\Header\Address;
use Pop\Mime\Part\Header\AddressList;

$list = new AddressList([
    new Address('john@doe.com', 'Doe, John'),
    new Address('jane@doe.com', 'Jane Doe'),
]);

$message = new Message();
$message->addHeader('To', $list->render());
```

[Top](#pop-mime)

#### Non-ASCII Header Values

A header value containing non-ASCII characters — most commonly a display name in an address, like
`José García`  — is automatically RFC 2047 encoded-word encoded when rendered, so it's safe to pass
UTF-8 text straight into `Address`, `Header\Value`, or any address-bearing header without encoding it
yourself first:

```php
use Pop\Mime\Part\Header\Address;

$address = new Address('jose@example.com', 'José García');
echo $address->render();
```

```text
=?UTF-8?B?Sm9zw6kgR2FyY8OtYQ==?= <jose@example.com>
```

Parsing does the inverse automatically — `AddressList::parse()`/`Address::parse()` decode an encoded-word
display name back to plain UTF-8 text. The encode/decode logic itself lives in
`Pop\Mime\Part\Header\EncodedWord` if you need it directly for something outside an address:

```php
use Pop\Mime\Part\Header\EncodedWord;

echo EncodedWord::encode('José García');            // =?UTF-8?B?Sm9zw6kgR2FyY8OtYQ==?=
echo EncodedWord::decode('=?UTF-8?B?Sm9zw6kgR2FyY8OtYQ==?='); // José García
```

Plain ASCII text is left untouched — encoding only kicks in when it's actually needed.

[Top](#pop-mime)

Message and Content IDs
------------------------

Every message benefits from a unique `Message-ID`, and an individual part — an inline image referenced
by `cid:`, for example — can carry its own `Content-ID`. Both are generated the same way, via
`generateId()`, which lives on `Part` (not just `Message`) so a nested part can generate its own ID too:

```php
use Pop\Mime\Message;

$message = new Message();
$message->setMessageId();
echo $message->getHeader('Message-ID');
```

```text
<b38becb9812bbe9a897ad65d9d3935d2@localhost>
```

`setMessageId()` generates and sets the header in one call; pass an explicit ID as the first argument if
you already have one, or a domain as the second argument to control what appears after the `@`
(defaults to `$_SERVER['SERVER_NAME']`, falling back to `localhost`):

```php
$message->setMessageId(null, 'example.com');
```

A nested part uses `setContentId()` the same way, which sets `Content-ID` instead of `Message-ID`:

```php
use Pop\Mime\Part;

$inlineImage = Part::attachment('logo.png');
$inlineImage->setContentId(null, 'example.com');
```

`generateId()` alone (without setting anything) is also available if you just want a raw ID string:

```php
$id = $message->generateId('example.com');
```

[Top](#pop-mime)

Multipart Messages
------------------

There is an interface to assist in easily creating multipart messages, instead of doing it the more manual way
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
        'contents' => file_get_contents(__DIR__ . '/test.pdf'),
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

$headers = Message::parseHeaders($headerString);
```

**Parsing a body string:**

If you happen to have the MIME body string, you can parse just that like below.
This will return an array of part strings, split on the boundary. The boundary has to be
passed in explicitly — it isn't detected from the body string itself, since a raw body string
has no header to read it from:

```php
use Pop\Mime\Message;

$parts = Message::parseBody($bodyString, $boundary);
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
