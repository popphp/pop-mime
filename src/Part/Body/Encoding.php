<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Mime\Part\Body;

/**
 * MIME part body encoding enum
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
enum Encoding: string
{
    case BASE64           = 'base64';
    case QUOTED_PRINTABLE = 'quoted-printable';
    case URL              = 'URL';
    case RAW_URL          = 'RAW_URL';
    case BINARY           = 'binary';
    case _7BIT            = '7bit';
    case _8BIT            = '8bit';

    /**
     * Map to the Content-Transfer-Encoding wire token, or null for encodings
     * that aren't a real MIME transfer encoding (URL/RAW_URL are used for
     * HTTP form-field encoding, not message wire format).
     *
     * @return ?string
     */
    public function toHeaderValue(): ?string
    {
        return match ($this) {
            self::URL, self::RAW_URL => null,
            default                  => $this->value,
        };
    }

    /**
     * Map a Content-Transfer-Encoding header value (case-insensitive) back
     * to its Encoding case, or null if unrecognized.
     *
     * @param  string $value
     * @return ?self
     */
    public static function fromHeaderValue(string $value): ?self
    {
        return self::tryFrom(strtolower($value));
    }
}
