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
namespace Pop\Mime\Part;

/**
 * MIME part body class
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Body
{

    /**
     * Content
     * @var ?string
     */
    protected ?string $content = null;

    /**
     * Pending file path - set by setContentFromFile(), read lazily by
     * render()/getContent(), never both this and $content set at once.
     * @var ?string
     */
    protected ?string $file = null;

    /**
     * Encoding
     * @var ?Body\Encoding
     */
    protected ?Body\Encoding $encoding = null;

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
     * @param ?string        $content
     * @param ?Body\Encoding $encoding
     * @param int|bool|null  $split
     */
    public function __construct(?string $content = null, ?Body\Encoding $encoding = null, int|bool|null $split = null)
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
        $this->content   = $content;
        $this->file      = null;
        $this->isEncoded = false;
        return $this;
    }

    /**
     * Set the body content from file
     *
     * @param  string         $file
     * @param  ?Body\Encoding $encoding
     * @param  int|bool|null  $split
     * @throws Exception
     * @return Body
     */
    public function setContentFromFile(string $file, ?Body\Encoding $encoding = null, int|bool|null $split = null): Body
    {
        $this->file      = $file;
        $this->content   = null;
        $this->isEncoded = false;
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
        $this->loadFileContent();
        return $this->content;
    }

    /**
     * Materialize a pending file's content, if one is set - lazy, so a
     * Body that's never rendered or read never touches the filesystem.
     *
     * @throws Exception
     * @return void
     */
    protected function loadFileContent(): void
    {
        if ($this->file === null) {
            return;
        }
        if (!file_exists($this->file)) {
            throw new Exception("Error: The file '" . $this->file . "' does not exist.");
        }
        $this->content = file_get_contents($this->file);
        $this->file    = null;
    }

    /**
     * Has body content
     *
     * @return bool
     */
    public function hasContent(): bool
    {
        return (($this->content !== null) || ($this->file !== null));
    }

    /**
     * Set the encoding
     *
     * @param  Body\Encoding $encoding
     * @return Body
     */
    public function setEncoding(Body\Encoding $encoding): Body
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * Get the encoding
     *
     * @return Body\Encoding|null
     */
    public function getEncoding(): Body\Encoding|null
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
        return ($this->encoding === Body\Encoding::BASE64);
    }

    /**
     * Is encoding quoted-printable
     *
     * @return bool
     */
    public function isQuotedPrintableEncoding(): bool
    {
        return ($this->encoding === Body\Encoding::QUOTED_PRINTABLE);
    }

    /**
     * Is encoding URL
     *
     * @return bool
     */
    public function isUrlEncoding(): bool
    {
        return ($this->encoding === Body\Encoding::URL);
    }

    /**
     * Is encoding raw URL
     *
     * @return bool
     */
    public function isRawUrlEncoding(): bool
    {
        return ($this->encoding === Body\Encoding::RAW_URL);
    }

    /**
     * Is encoding binary
     *
     * @return bool
     */
    public function isBinaryEncoding(): bool
    {
        return ($this->encoding === Body\Encoding::BINARY);
    }

    /**
     * Is encoding 7bit
     *
     * @return bool
     */
    public function is7BitEncoding(): bool
    {
        return ($this->encoding === Body\Encoding::_7BIT);
    }

    /**
     * Is encoding 8bit
     *
     * @return bool
     */
    public function is8BitEncoding(): bool
    {
        return ($this->encoding === Body\Encoding::_8BIT);
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
    public function setAsEncoded(bool $isEncoded): Body
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
        if (($this->file !== null) && (!$this->isEncoded) && ($this->encoding === Body\Encoding::BASE64)) {
            if (!file_exists($this->file)) {
                throw new Exception("Error: The file '" . $this->file . "' does not exist.");
            }
            $content         = file_get_contents('php://filter/convert.base64-encode/resource=' . $this->file);
            $this->content   = $content;
            $this->file      = null;
            $this->isEncoded = true;
        } else {
            $this->loadFileContent();
            $content = $this->content;

            if (!$this->isEncoded) {
                switch ($this->encoding) {
                    case Body\Encoding::BASE64:
                        $content = base64_encode($this->content);
                        $this->isEncoded = true;
                        break;
                    case Body\Encoding::QUOTED_PRINTABLE:
                        $content = quoted_printable_encode($this->content);
                        $this->isEncoded = true;
                        break;
                    case Body\Encoding::URL:
                        $content = urlencode($this->content);
                        $this->isEncoded = true;
                        break;
                    case Body\Encoding::RAW_URL:
                        $content = rawurlencode($this->content);
                        $this->isEncoded = true;
                        break;
                    case Body\Encoding::BINARY:
                    case Body\Encoding::_7BIT:
                    case Body\Encoding::_8BIT:
                        $this->isEncoded = true;
                        break;
                }
                if ($this->isEncoded) {
                    $this->content = $content;
                }
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
