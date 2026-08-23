<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Mime\Part\Header;

/**
 * RFC 2047 encoded-word encoder/decoder - has no dependency on ext-imap.
 * Decoding handles both B (base64) and Q (quoted-printable-style)
 * encoded-words, since real-world mail uses both; encoding only ever
 * produces B, which is simpler and unconditionally correct for any
 * content shape.
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
final class EncodedWord
{

    /**
     * Decode all RFC 2047 encoded-words found in a string. Never throws -
     * malformed or unrecognized encoded-word-shaped text is left as literal
     * output.
     *
     * @param  string $text
     * @return string
     */
    public static function decode(string $text): string
    {
        if (!str_contains($text, '=?')) {
            return $text;
        }

        $pattern = '/=\?[^?\s]+\?[BbQq]\?[^?]*\?=(?:\s+=\?[^?\s]+\?[BbQq]\?[^?]*\?=)*/';

        $result = preg_replace_callback($pattern, function ($matches) {
            return self::decodeRun($matches[0]);
        }, $text);

        return $result ?? $text;
    }

    /**
     * Find the byte ranges of every individual RFC 2047 encoded-word in a
     * string - used by Header::fold() to treat an encoded-word as an
     * atomic, unbreakable unit when choosing fold points. Folding between
     * two adjacent encoded-words, at their separating whitespace, is still
     * fine - only folding INSIDE one is not.
     *
     * @param  string $text
     * @return array
     */
    public static function findRanges(string $text): array
    {
        $ranges = [];
        if (preg_match_all('/=\?[^?\s]+\?[BbQq]\?[^?]*\?=/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $ranges[] = ['start' => $match[1], 'end' => $match[1] + strlen($match[0])];
            }
        }
        return $ranges;
    }

    /**
     * Encode a string into one or more RFC 2047 encoded-words, if it
     * contains anything outside printable US-ASCII. Returns the input
     * unchanged otherwise.
     *
     * @param  string $text
     * @param  string $charset
     * @return string
     */
    public static function encode(string $text, string $charset = 'UTF-8'): string
    {
        if (!self::needsEncoding($text)) {
            return $text;
        }

        $prefix           = '=?' . $charset . '?B?';
        $suffix           = '?=';
        $maxEncodedLength = 75 - strlen($prefix) - strlen($suffix);

        $words = [];
        foreach (self::splitIntoChunks($text, $maxEncodedLength) as $chunk) {
            $words[] = $prefix . base64_encode($chunk) . $suffix;
        }

        return implode(' ', $words);
    }

    /**
     * Decode a run of one or more whitespace-separated encoded-words,
     * dropping the whitespace between them per RFC 2047 §6.2
     *
     * @param  string $run
     * @return string
     */
    protected static function decodeRun(string $run): string
    {
        preg_match_all('/=\?([^?\s]+)\?([BbQq])\?([^?]*)\?=/', $run, $words, PREG_SET_ORDER);

        $decoded = '';
        foreach ($words as $word) {
            $decoded .= self::decodeWord($word[1], $word[2], $word[3]);
        }

        return $decoded;
    }

    /**
     * Decode a single encoded-word's charset/encoding/text into UTF-8
     *
     * @param  string $charset
     * @param  string $encoding
     * @param  string $encodedText
     * @return string
     */
    protected static function decodeWord(string $charset, string $encoding, string $encodedText): string
    {
        if (strcasecmp($encoding, 'B') === 0) {
            $bytes = base64_decode($encodedText, true);
            $bytes = ($bytes === false) ? '' : $bytes;
        } else {
            $bytes = quoted_printable_decode(str_replace('_', ' ', $encodedText));
        }

        return self::convertToUtf8($bytes, $charset);
    }

    /**
     * Convert decoded bytes to UTF-8, gracefully degrading if the charset
     * can't be converted (no mbstring, or an unrecognized charset name)
     *
     * @param  string $bytes
     * @param  string $charset
     * @return string
     */
    protected static function convertToUtf8(string $bytes, string $charset): string
    {
        if ((strcasecmp($charset, 'UTF-8') === 0) || (strcasecmp($charset, 'US-ASCII') === 0)) {
            return $bytes;
        }

        if (function_exists('mb_convert_encoding')) {
            try {
                return mb_convert_encoding($bytes, 'UTF-8', $charset);
            } catch (\Throwable) {
                return $bytes;
            }
        }

        return $bytes;
    }

    /**
     * Determine if a string contains anything outside printable US-ASCII
     * and therefore needs RFC 2047 encoding
     *
     * @param  string $text
     * @return bool
     */
    protected static function needsEncoding(string $text): bool
    {
        return (preg_match('/[^\x20-\x7E]/', $text) === 1);
    }

    /**
     * Split a UTF-8 string into byte-boundary-safe chunks, each short
     * enough that base64-encoding it stays within $maxEncodedLength chars.
     * Scans UTF-8 lead-byte patterns manually - no mbstring dependency
     * needed for this direction.
     *
     * @param  string $text
     * @param  int    $maxEncodedLength
     * @return array
     */
    protected static function splitIntoChunks(string $text, int $maxEncodedLength): array
    {
        $chunks  = [];
        $current = '';
        $length  = strlen($text);
        $pos     = 0;

        while ($pos < $length) {
            $byte = ord($text[$pos]);
            if (($byte & 0x80) === 0x00) {
                $charLen = 1;
            } else if (($byte & 0xE0) === 0xC0) {
                $charLen = 2;
            } else if (($byte & 0xF0) === 0xE0) {
                $charLen = 3;
            } else if (($byte & 0xF8) === 0xF0) {
                $charLen = 4;
            } else {
                $charLen = 1;
            }

            $char = substr($text, $pos, $charLen);

            if (($current !== '') && (strlen(base64_encode($current . $char)) > $maxEncodedLength)) {
                $chunks[] = $current;
                $current  = '';
            }

            $current .= $char;
            $pos     += $charLen;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

}
