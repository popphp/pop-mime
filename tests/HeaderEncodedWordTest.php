<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part\Header\EncodedWord;
use PHPUnit\Framework\TestCase;

class HeaderEncodedWordTest extends TestCase
{

    public function testDecodeBase64EncodedWord()
    {
        $this->assertEquals('Hello', EncodedWord::decode('=?UTF-8?B?SGVsbG8=?='));
    }

    public function testDecodeQuotedPrintableEncodedWord()
    {
        $this->assertEquals('Hello World', EncodedWord::decode('=?UTF-8?Q?Hello_World?='));
    }

    public function testDecodeQuotedPrintableWithHexEscape()
    {
        // =C3=A9 is the UTF-8 byte sequence for 'é'
        $this->assertEquals("H\xc3\xa9llo", EncodedWord::decode('=?UTF-8?Q?H=C3=A9llo?='));
    }

    public function testDecodeCollapsesWhitespaceBetweenAdjacentEncodedWords()
    {
        $sentence = str_repeat('Héllo Wörld, ', 20);
        $encoded  = EncodedWord::encode($sentence);

        $this->assertGreaterThan(1, substr_count($encoded, '=?UTF-8?B?'));
        $this->assertEquals($sentence, EncodedWord::decode($encoded));
    }

    public function testDecodeLeavesPlainTextUntouched()
    {
        $this->assertEquals('Hello World', EncodedWord::decode('Hello World'));
    }

    public function testDecodeLeavesTextAroundEncodedWordIntact()
    {
        $this->assertEquals('Subject: Hello', EncodedWord::decode('Subject: =?UTF-8?B?SGVsbG8=?='));
    }

    public function testDecodeInvalidBase64DegradesGracefully()
    {
        $result = EncodedWord::decode('=?UTF-8?B?not valid base64!!!?=');
        $this->assertIsString($result);
    }

    public function testDecodeUnterminatedEncodedWordLeftAsLiteralText()
    {
        $this->assertEquals('=?UTF-8?B?SGVsbG8', EncodedWord::decode('=?UTF-8?B?SGVsbG8'));
    }

    public function testEncodePureAsciiReturnsUnchanged()
    {
        $this->assertEquals('Hello World', EncodedWord::encode('Hello World'));
    }

    public function testEncodeNonAsciiProducesSingleEncodedWord()
    {
        $encoded = EncodedWord::encode('Héllo');
        $this->assertStringStartsWith('=?UTF-8?B?', $encoded);
        $this->assertStringEndsWith('?=', $encoded);
        $this->assertEquals(1, substr_count($encoded, '=?UTF-8?B?'));
    }

    public function testEncodeThenDecodeRoundTripsAccentedLatinText()
    {
        $original = 'Héllo Wörld, this has some accénted characters';
        $this->assertEquals($original, EncodedWord::decode(EncodedWord::encode($original)));
    }

    public function testEncodeThenDecodeRoundTripsMultiByteScript()
    {
        $original = '日本語のテスト';
        $this->assertEquals($original, EncodedWord::decode(EncodedWord::encode($original)));
    }

    public function testEncodeSplitsLongContentIntoMultipleWordsEachWithinLimit()
    {
        $original = str_repeat('日本語', 30);
        $encoded  = EncodedWord::encode($original);
        $words    = explode(' ', $encoded);

        $this->assertGreaterThan(1, count($words));
        foreach ($words as $word) {
            $this->assertLessThanOrEqual(75, strlen($word));
        }
        $this->assertEquals($original, EncodedWord::decode($encoded));
    }

    public function testDecodeNonUtf8Charset()
    {
        // =E9 is 'é' in ISO-8859-1 (single byte); \xc3\xa9 is 'é' in UTF-8 (two bytes)
        $this->assertEquals("H\xc3\xa9llo", EncodedWord::decode('=?ISO-8859-1?Q?H=E9llo?='));
    }

    public function testDecodeUnrecognizedCharsetDegradesGracefully()
    {
        $result = EncodedWord::decode('=?unknown-8bit?B?QQ==?=');
        $this->assertIsString($result);
    }

    public function testDecodeRfc2231LanguageTaggedCharsetDegradesGracefully()
    {
        $result = EncodedWord::decode('=?iso-8859-1*en?B?QQ==?=');
        $this->assertIsString($result);
    }

}
