<?php
declare(strict_types=1);
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
 * RFC 5322 address-list - a comma-separated collection of Address objects.
 * No group support - a group-shaped segment is treated as one opaque
 * Address rather than parsed structurally.
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
final class AddressList
{

    /**
     * @var Address[]
     */
    protected array $addresses = [];

    /**
     * @param Address[] $addresses
     */
    public function __construct(array $addresses = [])
    {
        foreach ($addresses as $address) {
            $this->addAddress($address);
        }
    }

    /**
     * Parse a comma-separated address list. Splits only on top-level
     * commas - a comma inside a quoted display name is part of a
     * QUOTED_STRING token, never a top-level delimiter, so it doesn't
     * split the list. Never throws.
     *
     * @param  string $text
     * @return AddressList
     */
    public static function parse(string $text): AddressList
    {
        $tokens = (new Lexer($text))->tokenize();
        $list   = new self();

        if (self::isGroupShaped($tokens)) {
            $trimmed = trim($text);
            if ($trimmed !== '') {
                $list->addAddress(new Address($trimmed));
            }
            return $list;
        }

        $segments = [[]];
        foreach ($tokens as $token) {
            if (($token->type === Lexer::DELIMITER) && ($token->value === ',')) {
                $segments[] = [];
            } else {
                $segments[count($segments) - 1][] = $token;
            }
        }

        foreach ($segments as $segmentTokens) {
            $segmentText = trim(self::spliceTokens($text, $segmentTokens));
            if ($segmentText !== '') {
                $list->addAddress(Address::parse($segmentText));
            }
        }

        return $list;
    }

    /**
     * A group ("display-name: mailbox-list;") is out of scope for this
     * class - detected by a top-level ":" appearing before any top-level
     * "<", AND a top-level ";" appearing somewhere after it (the group's
     * closing terminator), and treated as one opaque, unparsed Address
     * rather than being split on its internal commas. Both conditions are
     * required - a bare unquoted ":" alone (e.g. a display name like
     * "IT: Support" or "Ratio 3:1") is common in real-world addresses and
     * must not be misdetected as a group.
     *
     * @param  Token[] $tokens
     * @return bool
     */
    protected static function isGroupShaped(array $tokens): bool
    {
        $hasColonBeforeAngle = false;
        foreach ($tokens as $token) {
            if (($token->type === Lexer::DELIMITER) && ($token->value === ':')) {
                $hasColonBeforeAngle = true;
                break;
            }
            if (($token->type === Lexer::DELIMITER) && ($token->value === '<')) {
                break;
            }
        }

        if (!$hasColonBeforeAngle) {
            return false;
        }

        foreach ($tokens as $token) {
            if (($token->type === Lexer::DELIMITER) && ($token->value === ';')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string  $source
     * @param  Token[] $tokens
     * @return string
     */
    protected static function spliceTokens(string $source, array $tokens): string
    {
        if (empty($tokens)) {
            return '';
        }
        $first = $tokens[array_key_first($tokens)];
        $last  = $tokens[array_key_last($tokens)];
        return substr($source, $first->start, $last->end - $first->start);
    }

    /**
     * @param  Address|string $address
     * @param  ?string        $name
     * @return AddressList
     */
    public function addAddress(Address|string $address, ?string $name = null): AddressList
    {
        $this->addresses[] = ($address instanceof Address) ? $address : new Address($address, $name);
        return $this;
    }

    /**
     * @return Address[]
     */
    public function getAddresses(): array
    {
        return $this->addresses;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->addresses);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        return implode(', ', array_map(fn($address) => $address->render(), $this->addresses));
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
