<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Mime;

use Pop\Mime\Part\Exception;

/**
 * MIME message part class
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    2.0.1
 */
class Part
{

    /**
     * Headers
     * @var array
     */
    protected array $headers = [];

    /**
     * Body
     * @var ?Part\Body
     */
    protected ?Part\Body $body = null;

    /**
     * Nested parts
     * @var array
     */
    protected array $parts = [];

    /**
     * Subtype
     * @var ?string
     */
    protected ?string $subType = null;

    /**
     * Boundary
     * @var ?string
     */
    protected ?string $boundary = null;

    /**
     * Constructor
     *
     * Instantiate the mime part object
     *
     */
    public function __construct()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $a) {
                    if ($a instanceof Part\Header) {
                        $this->addHeader($a);
                    } else if ($a instanceof Part) {
                        $this->addPart($a);
                    }
                }
            } else if ($arg instanceof Part\Header) {
                $this->addHeader($arg);
            } else if ($arg instanceof Part\Body) {
                $this->setBody($arg);
            }
        }
    }

    /**
     * Add a header
     *
     * @param  Part\Header|string  $header
     * @param  ?string             $value
     * @return Part
     */
    public function addHeader(Part\Header|string $header, ?string $value = null): Part
    {
        if ($header instanceof Part\Header) {
            $this->headers[$header->getName()] = $header;
        } else {
            $this->headers[$header] = new Part\Header($header, $value);
        }

        return $this;
    }

    /**
     * Add headers
     *
     * @param  array $headers
     * @return Part
     */
    public function addHeaders(array $headers): Part
    {
        foreach ($headers as $header => $value) {
            if ($value instanceof Part\Header) {
                $this->addHeader($value);
            } else {
                $this->addHeader($header, $value);
            }
        }
        return $this;
    }

    /**
     * Get headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get headers as array
     *
     * @return array
     */
    public function getHeadersAsArray(): array
    {
        $headers = [];

        foreach ($this->headers as $header) {
            $headers[$header->getName()] = $header->getValuesAsStrings();
        }

        return $headers;
    }

    /**
     * Get headers
     *
     * @param  string $name
     * @return Part\Header|null
     */
    public function getHeader(string $name): Part\Header|null
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Has header
     *
     * @param  string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return (isset($this->headers[$name]));
    }

    /**
     * Has headers
     *
     * @return bool
     */
    public function hasHeaders(): bool
    {
        return (count($this->headers) > 0);
    }

    /**
     * Remove header
     *
     * @param  string $name
     * @return Part
     */
    public function removeHeader(string $name): Part
    {
        if (isset($this->headers[$name])) {
            unset($this->headers[$name]);
        }
        return $this;
    }

    /**
     * Set body
     *
     * @param  Part\Body|string $body
     * @return Part
     */
    public function setBody(Part\Body|string$body): Part
    {
        $this->body = ($body instanceof Part\Body) ? $body : new Part\Body($body);
        return $this;
    }

    /**
     * Add file as body
     *
     * @param string   $file
     * @param string   $disposition
     * @param string   $encoding
     * @param int|bool $split
     * @throws Exception
     * @return Part
     */
    public function addFile(
        string $file, string $disposition = 'attachment', string $encoding = Part\Body::BASE64, int|bool $split = true
    ): Part
    {
        if ($disposition !== null) {
            $header = new Part\Header('Content-Disposition');
            $header->addValue($disposition, null, ['filename' => basename($file)]);
            $this->addHeader($header);
        }
        $this->body = new Part\Body();
        $this->body->setContentFromFile($file, $encoding, $split);
        return $this;
    }

    /**
     * Get body
     *
     * @return Part\Body
     */
    public function getBody(): Part\Body
    {
        return $this->body;
    }

    /**
     * Has body
     *
     * @return bool
     */
    public function hasBody(): bool
    {
        return ($this->body !== null);
    }

    /**
     * Add a nested part
     *
     * @param  Part $part
     * @return Part
     */
    public function addPart(Part $part): Part
    {
        $this->parts[] = $part;
        return $this;
    }

    /**
     * Add nested parts
     *
     * @param  array $parts
     * @return Part
     */
    public function addParts(array $parts): Part
    {
        foreach ($parts as $part) {
            if (is_array($part)) {
                $file = false;
                foreach ($part as $p) {
                    if (($p->hasBody()) && ($p->getBody()->isFile())) {
                        $file = true;
                    }
                }
                $subType  = ($file) ? 'mixed' : 'alternative';
                $subParts = new Part();
                $subParts->setSubType($subType);
                $subParts->addParts($part);
                $this->addPart($subParts);
            } else {
                $this->addPart($part);
            }
        }
        return $this;
    }

    /**
     * Get nested parts
     *
     * @return array
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    /**
     * Has nested parts
     *
     * @return bool
     */
    public function hasParts(): bool
    {
        return (count($this->parts) > 0);
    }

    /**
     * Set subtype
     *
     * @param  string $subType
     * @return Part
     */
    public function setSubType(string $subType): Part
    {
        $this->subType = $subType;
        return $this;
    }

    /**
     * Get subtype
     *
     * @return string
     */
    public function getSubType(): string
    {
        return $this->subType;
    }

    /**
     * Has subtype
     *
     * @return bool
     */
    public function hasSubType(): bool
    {
        return ($this->subType !== null);
    }

    /**
     * Set boundary
     *
     * @param  string $boundary
     * @return Part
     */
    public function setBoundary(string $boundary): Part
    {
        $this->boundary = $boundary;
        return $this;
    }

    /**
     * Get boundary
     *
     * @return string
     */
    public function getBoundary(): string
    {
        return $this->boundary;
    }

    /**
     * Has boundary
     *
     * @return bool
     */
    public function hasBoundary(): bool
    {
        return ($this->boundary !== null);
    }

    /**
     * Generate boundary
     *
     * @return string
     */
    public function generateBoundary(): string
    {
        $this->setBoundary(sha1(uniqid()));
        return $this->boundary;
    }

    /**
     * Has attachment (check via a header)
     *
     * @return bool
     */
    public function hasAttachment(): bool
    {
        $result = false;

        foreach ($this->headers as $header) {
            if ($header->isAttachment()) {
                $result = true;
                break;
            }
        }

        if ((!$result) && ($this->hasParts())) {
            $result = $this->hasAttachments();
        }

        return $result;
    }

    /**
     * Does message have attachments (check via parts)
     *
     * @return bool
     */
    public function hasAttachments(): bool
    {
        foreach ($this->parts as $part) {
            if ($part->hasAttachment()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get attachments
     *
     * @return array
     */
    public function getAttachments(): array
    {
        $attachments = [];

        foreach ($this->parts as $part) {
            if ($part->getBody()->isFile()) {
                $attachments[] = $part;
            }
        }

        return $attachments;
    }

    /**
     * Get content-type
     *
     * @return string
     */
    public function getContentType(): string
    {
        $contentType = null;

        if ($this->hasHeader('Content-Type') && (count($this->getHeader('Content-Type')->getValues()) == 1)) {
            $contentType = (string)$this->getHeader('Content-Type')->getValue(0);
        }

        return $contentType;
    }

    /**
     * Get attachment filename
     *
     * @return string|null
     */
    public function getFilename(): string|null
    {
        $filename = null;

        if ($this->getBody()->isFile()) {
            // Check Content-Disposition header (standard)
            if ($this->hasHeader('Content-Disposition') && (count($this->getHeader('Content-Disposition')->getValues()) == 1)) {
                $header = $this->getHeader('Content-Disposition');
                if ($header->getValue(0)->hasParameter('filename')) {
                    $filename = $header->getValue(0)->getParameter('filename');
                } else if ($header->getValue(0)->hasParameter('name')) {
                    $filename = $header->getValue(0)->getParameter('name');
                }
            }

            // Else, check Content-Type header (non-standard)
            if ($filename === null) {
                if ($this->hasHeader('Content-Type') && (count($this->getHeader('Content-Type')->getValues()) == 1)) {
                    $header = $this->getHeader('Content-Type');
                    if ($header->getValue(0)->hasParameter('filename')) {
                        $filename = $header->getValue(0)->getParameter('filename');
                    } else if ($header->getValue(0)->hasParameter('name')) {
                        $filename = $header->getValue(0)->getParameter('name');
                    }
                }
            }

            // Else, check Content-Description header (non-standard)
            if ($filename === null) {
                if ($this->hasHeader('Content-Description') && (count($this->getHeader('Content-Description')->getValues()) == 1)) {
                    $header = $this->getHeader('Content-Description');
                    if ($header->getValue(0)->hasParameter('filename')) {
                        $filename = $header->getValue(0)->getParameter('filename');
                    } else if ($header->getValue(0)->hasParameter('name')) {
                        $filename = $header->getValue(0)->getParameter('name');
                    }
                }
            }
        }

        // Decode filename, if encoded
        if (($filename !== null) && (function_exists('imap_mime_header_decode')) &&
            ((str_contains($filename, 'UTF')) || (str_contains($filename, 'ISO')) ||
                (str_contains($filename, '?')) || (str_contains($filename, '=')))) {
            $filenameAry = imap_mime_header_decode($filename);
            if (isset($filenameAry[0]) && isset($filenameAry[0]->text)) {
                $filename = $filenameAry[0]->text;
            }
        }

        return $filename;
    }

    /**
     * Get contents (decoded)
     *
     * @return mixed
     */
    public function getContents(): mixed
    {
        $content = $this->body->getContent();

        if ($this->body->isEncoded()) {
            if ($this->body->isBase64Encoding()) {
                $content = base64_decode($content);
            } else if ($this->body->isQuotedEncoding()) {
                $content = quoted_printable_decode($content);
            } else if ($this->body->isUrlEncoding()) {
                $content = urldecode($content);
            } else if ($this->body->isRawUrlEncoding()) {
                $content = rawurldecode($content);
            }
        }

        return $content;
    }

    /**
     * Render the part headers
     *
     * @return string
     */
    public function renderHeaders(): string
    {
        return implode("\r\n", $this->headers) . "\r\n\r\n";
    }

    /**
     * Render the parts
     *
     * @param  bool $preamble
     * @param  bool $headers
     * @return string
     */
    public function renderParts(bool $preamble = true, bool $headers = true): string
    {
        $parts = '';

        $boundary = (!$this->hasBoundary()) ? $this->generateBoundary() : $this->boundary;
        if (!($this->hasHeader('Content-Type')) && ($this->hasSubType())) {
            $this->addHeader(
                new Part\Header('Content-Type', new Part\Header\Value('multipart/' . $this->subType, null, ['boundary' =>  $boundary]))
            );
        }
        if (($headers) && ($this->hasHeaders())) {
            $parts .= $this->renderHeaders();
        }
        if ($preamble) {
            $parts .= "This is a multi-part message in MIME format.\r\n";
        }
        foreach ($this->parts as $part) {
            $parts .= "--" . $boundary . "\r\n" . $part . "\r\n";
        }

        $parts .= "--" . $boundary . "--\r\n";

        return $parts;
    }

    /**
     * Render the part body
     *
     * @return string
     */
    public function renderBody(): string
    {
        return $this->body->render();
    }

    /**
     * Render the part
     *
     * @param  bool $preamble
     * @return string
     */
    public function render(bool $preamble = true): string
    {
        $messagePart = '';

        if ($this->hasParts()) {
            $messagePart .= $this->renderParts($preamble);
        } else if ($this->hasBody()) {
            if ((!$this->hasHeader('Content-Transfer-Encoding')) && ($this->body->hasEncoding())) {
                $encoding = null;
                if ($this->body->isBase64Encoding()) {
                    $encoding = 'base64';
                } else if ($this->body->isQuotedEncoding()) {
                    $encoding = 'quoted-printable';
                }
                if ($encoding !== null) {
                    $this->addHeader(new Part\Header('Content-Transfer-Encoding', $encoding));
                }
            }
            if ($this->hasHeaders()) {
                $messagePart .= $this->renderHeaders();
            }
            $messagePart .= $this->renderBody();
        }

        return $messagePart;
    }


    /**
     * Render the part raw (no headers or preamble)
     *
     * @return string
     */
    public function renderRaw(): string
    {
        return $this->renderParts(false, false);
    }

    /**
     * Render the part
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
