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
            $this->addPart($part);
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
     * Render the part
     *
     * @return string
     */
    public function render()
    {
        $messagePart = '';

        if ($this->hasParts()) {
            $boundary = (!$this->hasBoundary()) ? $this->generateBoundary() : $this->boundary;
            if (!($this->hasHeader('Content-Type')) && ($this->hasSubType())) {
                $this->addHeader(
                    new Part\Header('Content-Type', 'multipart/' . $this->subType . '; boundary=' . $boundary)
                );
            }
            $messagePart .= implode("\r\n", $this->headers) . "\r\n\r\n";
            $messagePart .= "This is a multi-part message in MIME format.\r\n";

            foreach ($this->parts as $part) {
                $messagePart .= "--" . $boundary . "\r\n" . $part . "\r\n";
            }
            $messagePart .= "--" . $boundary . "--\r\n";
        } else if ($this->hasBody()) {
            if (($this->body->isFile()) && (!$this->hasHeader('Content-Transfer-Encoding'))) {
                $encoding = ($this->body->isBase64()) ? 'base64' : 'binary';
                $this->addHeader(
                    new Part\Header('Content-Transfer-Encoding', $encoding)
                );
            }
            if ($this->hasHeaders()) {
                $messagePart .= implode("\r\n", $this->headers) . "\r\n\r\n";
            }
            $messagePart .= $this->body->render();
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