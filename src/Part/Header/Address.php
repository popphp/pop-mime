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
namespace Pop\Mime\Part\Header;

/**
 * RFC 5322 address - a single mailbox (optional display name + addr-spec).
 * Not a full RFC 5322 grammar: no groups, no obs-* forms - malformed or
 * unusual input degrades to a bare address rather than throwing.
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
final class Address
{

    /**
     * Display name, already RFC 2047-decoded
     * @var ?string
     */
    protected ?string $name = null;

    /**
     * The addr-spec (e.g. "john@example.com"), kept as an opaque string
     * @var string
     */
    protected string $address;

    /**
     * @param string  $address
     * @param ?string $name
     */
    public function __construct(string $address, ?string $name = null)
    {
        $this->address = $address;
        $this->name    = $name;
    }

    /**
     * Parse a single address (no comma-splitting - see AddressList for
     * multiple addresses). Never throws - malformed input (unbalanced or
     * absent angle brackets) degrades to treating the whole string as a
     * bare address.
     *
     * @param  string $text
     * @return Address
     */
    public static function parse(string $text): Address
    {
        $trimmed = trim($text);
        $tokens  = (new Lexer($trimmed))->tokenize();

        $openIndex  = null;
        $closeIndex = null;
        foreach ($tokens as $i => $token) {
            if (($token->type === Lexer::DELIMITER) && ($token->value === '<') && ($openIndex === null)) {
                $openIndex = $i;
            } else if (($token->type === Lexer::DELIMITER) && ($token->value === '>') &&
                ($openIndex !== null) && ($closeIndex === null)) {
                $closeIndex = $i;
            }
        }

        if (($openIndex === null) || ($closeIndex === null)) {
            return new self($trimmed);
        }

        foreach (array_slice($tokens, $closeIndex + 1) as $token) {
            if ($token->type !== Lexer::FWS) {
                return new self($trimmed);
            }
        }

        $name       = self::renderName(array_slice($tokens, 0, $openIndex));
        $openEnd    = $tokens[$openIndex]->end;
        $closeToken = $tokens[$closeIndex];
        $address    = trim(substr($trimmed, $openEnd, $closeToken->start - $openEnd));

        return new self($address, $name);
    }

    /**
     * @param  Token[] $tokens
     * @return ?string
     */
    protected static function renderName(array $tokens): ?string
    {
        $value = '';
        foreach ($tokens as $token) {
            $value .= ($token->type === Lexer::FWS) ? ' ' : $token->value;
        }
        $value = trim($value);

        return ($value !== '') ? EncodedWord::decode($value) : null;
    }

    /**
     * @return ?string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param  ?string $name
     * @return Address
     */
    public function setName(?string $name): Address
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param  string $address
     * @return Address
     */
    public function setAddress(string $address): Address
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        if ($this->name === null) {
            return $this->address;
        }

        $encoded = EncodedWord::encode($this->name);

        if (($encoded === $this->name) && self::needsQuoting($this->name)) {
            $encoded = '"' . addcslashes($encoded, '"\\') . '"';
        }

        return $encoded . ' <' . $this->address . '>';
    }

    /**
     * @param  string $name
     * @return bool
     */
    protected static function needsQuoting(string $name): bool
    {
        return (bool)preg_match('/[,;:<>()\[\]@\\\\"]/', $name);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
