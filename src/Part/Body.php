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
namespace Pop\Mime\Part;

/**
 * MIME part body class
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    2.0.1
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
     * @var ?string
     */
    protected ?string $content = null;

    /**
     * Encoding
     * @var ?string
     */
    protected ?string $encoding = null;

    /**
     * Chunk split
     * @var int|bool|null
     */
    protected int|bool|null $split = null;

    /**
     * Is file flag
     * @var bool
     */
    protected bool $isFile = false;

    /**
     * Is encoded flag
     * @var bool
     */
    protected bool $isEncoded = false;

    /**
     * Constructor
     *
     * Instantiate the body object
     *
     * @param ?string       $content
     * @param ?string       $encoding
     * @param int|bool|null $split
     */
    public function __construct(?string $content = null, ?string $encoding = null, int|bool|null $split = null)
    {
        if ($content !== null) {
            $this->setContent($content);
        }
        if ($encoding !== null) {
            $this->setEncoding($encoding);
        }
        if ($split !== null) {
            $this->setSplit($split);
        }
    }

    /**
     * Set the body content
     *
     * @param  string $content
     * @return Body
     */
    public function setContent(string $content): Body
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Set the body content from file
     *
     * @param  string        $file
     * @param  ?string       $encoding
     * @param  int|bool|null $split
     * @throws Exception
     * @return Body
     */
    public function setContentFromFile(string $file, ?string $encoding = null, int|bool|null $split = null): Body
    {
        if (!file_exists($file)) {
            throw new Exception("Error: The file '" . $file . "' does not exist.");
        }

        $this->content = file_get_contents($file);
        $this->setAsFile(true);

        if ($encoding !== null) {
            $this->setEncoding($encoding);
        }
        if ($split !== null) {
            $this->setSplit($split);
        }

        return $this;
    }

    /**
     * Get the body content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Has body content
     *
     * @return bool
     */
    public function hasContent(): bool
    {
        return ($this->content !== null);
    }

    /**
     * Set the encoding
     *
     * @param  string $encoding
     * @return Body
     */
    public function setEncoding(string $encoding): Body
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
     * @return string|null
     */
    public function getEncoding(): string|null
    {
        return $this->encoding;
    }

    /**
     * Has encoding
     *
     * @return bool
     */
    public function hasEncoding(): bool
    {
        return ($this->encoding !== null);
    }

    /**
     * Is encoding base64
     *
     * @return bool
     */
    public function isBase64Encoding(): bool
    {
        return ($this->encoding == self::BASE64);
    }

    /**
     * Is encoding quoted-printable
     *
     * @return bool
     */
    public function isQuotedEncoding(): bool
    {
        return ($this->encoding == self::QUOTED);
    }

    /**
     * Is encoding URL
     *
     * @return bool
     */
    public function isUrlEncoding(): bool
    {
        return ($this->encoding == self::URL);
    }

    /**
     * Is encoding raw URL
     *
     * @return bool
     */
    public function isRawUrlEncoding(): bool
    {
        return ($this->encoding == self::RAW_URL);
    }

    /**
     * Set the split
     *
     * @param  int|bool $split
     * @return Body
     */
    public function setSplit(int|bool $split): Body
    {
        $this->split = $split;
        return $this;
    }

    /**
     * Get the split
     *
     * @return int|bool
     */
    public function getSplit(): int|bool
    {
        return $this->split;
    }

    /**
     * Has split
     *
     * @return bool
     */
    public function hasSplit(): bool
    {
        return ($this->split !== null);
    }

    /**
     * Set as file
     *
     * @param  bool $isFile
     * @return Body
     */
    public function setAsFile(bool $isFile): Body
    {
        $this->isFile = (bool)$isFile;
        return $this;
    }

    /**
     * Is file
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return $this->isFile;
    }

    /**
     * Set as encoded
     *
     * @param  bool $isEncoded
     * @return Body
     */
    public function setAsEncoded(bool $isEncoded): body
    {
        $this->isEncoded = (bool)$isEncoded;
        return $this;
    }

    /**
     * Is encoded
     *
     * @return bool
     */
    public function isEncoded(): bool
    {
        return $this->isEncoded;
    }

    /**
     * Render the body
     *
     * @return string
     */
    public function render(): string
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

        if ($this->split !== null) {
            $content = ($this->split === true) ? chunk_split($content) : chunk_split($content, (int)$this->split);
        }

        return (string)$content;
    }

    /**
     * Render the body
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
