<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Mime;

/**
 * MIME message part class
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.0.0
 */
class Part
{

    /**
     * Headers
     * @var array
     */
    protected $headers = [];

    /**
     * Body
     * @var Part\Body
     */
    protected $body = null;

    /**
     * Nested parts
     * @var array
     */
    protected $parts = [];

    /**
     * Sub-type
     * @var string
     */
    protected $subType = null;

    /**
     * Boundary
     * @var string
     */
    protected $boundary = null;

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
     * @param  Part\Header|string $header
     * @param  string             $value
     * @return Part
     */
    public function addHeader($header, $value = null)
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
    public function addHeaders(array $headers)
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
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get headers as array
     *
     * @return array
     */
    public function getHeadersAsArray()
    {
        $headers = [];

        foreach ($this->headers as $header) {
            $headerValue = $header->getValue();
            if ($header->hasParameters()) {
                $headerValue .= '; ' . $header->getParametersAsString();
            }
            $headers[$header->getName()] = $headerValue;
        }

        return $headers;
    }

    /**
     * Get headers
     *
     * @param  string $name
     * @return Part\Header
     */
    public function getHeader($name)
    {
        return (isset($this->headers[$name])) ? $this->headers[$name] : null;
    }

    /**
     * Has header
     *
     * @param  string $name
     * @return boolean
     */
    public function hasHeader($name)
    {
        return (isset($this->headers[$name]));
    }

    /**
     * Has headers
     *
     * @return boolean
     */
    public function hasHeaders()
    {
        return (count($this->headers) > 0);
    }

    /**
     * Remove header
     *
     * @param  string $name
     * @return Part
     */
    public function removeHeader($name)
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
    public function setBody($body)
    {
        $this->body = ($body instanceof Part\Body) ? $body : new Part\Body($body);
        return $this;
    }

    /**
     * Add file as body
     *
     * @param  string      $file
     * @param  string      $disposition
     * @param  string      $encoding
     * @param  int|boolean $split
     * @return Part
     */
    public function addFile($file, $disposition = 'attachment', $encoding = Part\Body::BASE64, $split = true)
    {
        if (null !== $disposition) {
            $header = new Part\Header('Content-Disposition', $disposition);
            $header->addParameter('filename', basename($file));
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
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Has body
     *
     * @return boolean
     */
    public function hasBody()
    {
        return (null !== $this->body);
    }

    /**
     * Add a nested part
     *
     * @param  Part $part
     * @return Part
     */
    public function addPart(Part $part)
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
    public function addParts(array $parts)
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
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * Has nested parts
     *
     * @return boolean
     */
    public function hasParts()
    {
        return (count($this->parts) > 0);
    }

    /**
     * Set sub-type
     *
     * @param  string $subType
     * @return Part
     */
    public function setSubType($subType)
    {
        $this->subType = $subType;
        return $this;
    }

    /**
     * Get sub-type
     *
     * @return string
     */
    public function getSubType()
    {
        return $this->subType;
    }

    /**
     * Has sub-type
     *
     * @return boolean
     */
    public function hasSubType()
    {
        return (null !== $this->subType);
    }

    /**
     * Set boundary
     *
     * @param  string $boundary
     * @return Part
     */
    public function setBoundary($boundary)
    {
        $this->boundary = $boundary;
        return $this;
    }

    /**
     * Get boundary
     *
     * @return string
     */
    public function getBoundary()
    {
        return $this->boundary;
    }

    /**
     * Has boundary
     *
     * @return boolean
     */
    public function hasBoundary()
    {
        return (null !== $this->boundary);
    }

    /**
     * Generate boundary
     *
     * @return string
     */
    public function generateBoundary()
    {
        $this->setBoundary(sha1(uniqid()));
        return $this->boundary;
    }

    /**
     * Has attachment (check via a header)
     *
     * @return boolean
     */
    public function hasAttachment()
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
     * @return boolean
     */
    public function hasAttachments()
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
    public function getAttachments()
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
    public function getContentType()
    {
        $contentType = null;

        if ($this->hasHeader('Content-Type')) {
            $contentType = $this->getHeader('Content-Type')->getValue();
        }

        return $contentType;
    }

    /**
     * Get attachment filename
     *
     * @return string
     */
    public function getFilename()
    {
        $filename = null;

        if ($this->getBody()->isFile()) {
            // Check Content-Disposition header (standard)
            if ($this->hasHeader('Content-Disposition')) {
                $header = $this->getHeader('Content-Disposition');
                if ($header->hasParameter('filename')) {
                    $filename = $header->getParameter('filename');
                } else if ($header->hasParameter('name')) {
                    $filename = $header->getParameter('name');
                }
            }

            // Else, check Content-Type header (non-standard)
            if (null === $filename) {
                if ($this->hasHeader('Content-Type')) {
                    $header = $this->getHeader('Content-Type');
                    if ($header->hasParameter('filename')) {
                        $filename = $header->getParameter('filename');
                    } else if ($header->hasParameter('name')) {
                        $filename = $header->getParameter('name');
                    }
                }
            }

            // Else, check Content-Description header (non-standard)
            if (null === $filename) {
                if ($this->hasHeader('Content-Description')) {
                    $header = $this->getHeader('Content-Description');
                    if ($header->hasParameter('filename')) {
                        $filename = $header->getParameter('filename');
                    } else if ($header->hasParameter('name')) {
                        $filename = $header->getParameter('name');
                    }
                }
            }
        }

        // Decode filename, if encoded
        if ((null !== $filename) && (function_exists('imap_mime_header_decode')) &&
            ((strpos($filename, 'UTF') !== false) || (strpos($filename, 'ISO') !== false) ||
                (strpos($filename, '?') !== false) || (strpos($filename, '=') !== false))) {
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
    public function getContents()
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
    public function renderHeaders()
    {
        return implode("\r\n", $this->headers) . "\r\n\r\n";
    }

    /**
     * Render the parts
     *
     * @param  boolean $preamble
     * @return string
     */
    public function renderParts($preamble = true)
    {
        $parts = '';

        $boundary = (!$this->hasBoundary()) ? $this->generateBoundary() : $this->boundary;
        if (!($this->hasHeader('Content-Type')) && ($this->hasSubType())) {
            $this->addHeader(
                new Part\Header('Content-Type', 'multipart/' . $this->subType . '; boundary=' . $boundary)
            );
        }
        if ($this->hasHeaders()) {
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
    public function renderBody()
    {
        return $this->body->render();
    }

    /**
     * Render the part
     *
     * @param  boolean $preamble
     * @return string
     */
    public function render($preamble = true)
    {
        $messagePart = '';

        if ($this->hasParts()) {
            $messagePart .= $this->renderParts($preamble);
        } else if ($this->hasBody()) {
            if (($this->body->isFile()) && (!$this->hasHeader('Content-Transfer-Encoding'))) {
                $encoding = ($this->body->isBase64Encoding()) ? 'base64' : 'binary';
                $this->addHeader(
                    new Part\Header('Content-Transfer-Encoding', $encoding)
                );
            } else if ((!$this->hasHeader('Content-Transfer-Encoding')) && $this->body->isQuotedEncoding()) {
                $this->addHeader(
                    new Part\Header('Content-Transfer-Encoding', 'quoted-printable')
                );
            }
            if ($this->hasHeaders()) {
                $messagePart .= $this->renderHeaders();
            }
            $messagePart .= $this->renderBody();
        }

        return $messagePart;
    }

    /**
     * Render the part
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}