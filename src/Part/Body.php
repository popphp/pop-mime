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
namespace Pop\Mime\Part;

/**
 * MIME part body class
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.0.0
 */
class Body
{

    /**
     * Encoding constants
     * @var string
     */
    const BASE64  = 'BASE64';
    const QUOTED  = 'QUOTED';
    const URL     = 'URL';
    const RAW_URL = 'RAW_URL';

    /**
     * Content
     * @var string
     */
    protected $content = null;

    /**
     * Encoding
     * @var string
     */
    protected $encoding = null;

    /**
     * Chunk split
     * @var int|boolean
     */
    protected $split = null;

    /**
     * Is file flag
     * @var boolean
     */
    protected $isFile = false;

    /**
     * Is encoded flag
     * @var boolean
     */
    protected $isEncoded = false;

    /**
     * Constructor
     *
     * Instantiate the body object
     *
     * @param string $content
     * @param string $encoding
     * @param int    $split
     */
    public function __construct($content = null, $encoding = null, $split = null)
    {
        if (null !== $content) {
            $this->setContent($content);
        }
        if (null !== $encoding) {
            $this->setEncoding($encoding);
        }
        if (null !== $split) {
            $this->setSplit($split);
        }
    }

    /**
     * Set the body content
     *
     * @param  string $content
     * @return Body
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Set the body content from file
     *
     * @param  string $file
     * @param  string $encoding
     * @param  string $split
     * @throws Exception
     * @return Body
     */
    public function setContentFromFile($file, $encoding = null, $split = null)
    {
        if (!file_exists($file)) {
            throw new Exception("Error: The file '" . $file . "' does not exist.");
        }

        $this->content = file_get_contents($file);
        $this->setAsFile(true);

        if (null !== $encoding) {
            $this->setEncoding($encoding);
        }
        if (null !== $split) {
            $this->setSplit($split);
        }

        return $this;
    }

    /**
     * Get the body content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Has body content
     *
     * @return boolean
     */
    public function hasContent()
    {
        return (null !== $this->content);
    }

    /**
     * Set the encoding
     *
     * @param  string $encoding
     * @return Body
     */
    public function setEncoding($encoding)
    {
        switch ($encoding) {
            case self::BASE64:
            case self::QUOTED:
            case self::URL:
            case self::RAW_URL:
            $this->encoding = $encoding;
        }
        return $this;
    }

    /**
     * Get the encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Has encoding
     *
     * @return boolean
     */
    public function hasEncoding()
    {
        return (null !== $this->encoding);
    }

    /**
     * Is encoding base64
     *
     * @return boolean
     */
    public function isBase64Encoding()
    {
        return ($this->encoding == self::BASE64);
    }

    /**
     * Is encoding quoted-printable
     *
     * @return boolean
     */
    public function isQuotedEncoding()
    {
        return ($this->encoding == self::QUOTED);
    }

    /**
     * Is encoding URL
     *
     * @return boolean
     */
    public function isUrlEncoding()
    {
        return ($this->encoding == self::URL);
    }

    /**
     * Is encoding raw URL
     *
     * @return boolean
     */
    public function isRawUrlEncoding()
    {
        return ($this->encoding == self::RAW_URL);
    }

    /**
     * Set the split
     *
     * @param  int|boolean $split
     * @return Body
     */
    public function setSplit($split)
    {
        $this->split = $split;
        return $this;
    }

    /**
     * Get the split
     *
     * @return int|boolean
     */
    public function getSplit()
    {
        return $this->split;
    }

    /**
     * Has split
     *
     * @return boolean
     */
    public function hasSplit()
    {
        return (null !== $this->split);
    }

    /**
     * Set as file
     *
     * @param  boolean $isFile
     * @return Body
     */
    public function setAsFile($isFile)
    {
        $this->isFile = (bool)$isFile;
        return $this;
    }

    /**
     * Is file
     *
     * @return boolean
     */
    public function isFile()
    {
        return $this->isFile;
    }

    /**
     * Set as encoded
     *
     * @param  boolean $isEncoded
     * @return Body
     */
    public function setAsEncoded($isEncoded)
    {
        $this->isEncoded = (bool)$isEncoded;
        return $this;
    }

    /**
     * Is encoded
     *
     * @return boolean
     */
    public function isEncoded()
    {
        return $this->isEncoded;
    }

    /**
     * Render the body
     *
     * @return string
     */
    public function render()
    {
        $content = $this->content;

        if (!$this->isEncoded) {
            switch ($this->encoding) {
                case self::BASE64:
                    $content = base64_encode($this->content);
                    $this->isEncoded = true;
                    break;
                case self::QUOTED:
                    $content = quoted_printable_encode($this->content);
                    $this->isEncoded = true;
                    break;
                case self::URL:
                    $content = urlencode($this->content);
                    $this->isEncoded = true;
                    break;
                case self::RAW_URL:
                    $content = rawurlencode($this->content);
                    $this->isEncoded = true;
                    break;
            }
        }

        if (null !== $this->split) {
            $content = ($this->split === true) ? chunk_split($content) : chunk_split($content, (int)$this->split);
        }

        return $content;
    }

    /**
     * Render the body
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}