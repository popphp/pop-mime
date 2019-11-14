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

use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;

/**
 * MIME message class
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.0.0
 */
class Message extends Part
{

    /**
     * Parse message
     *
     * @param  string $messageString
     * @return Message
     */
    public static function parseMessage($messageString)
    {
        $headerString = substr($messageString, 0, strpos($messageString, "\r\n\r\n"));
        $bodyString   = substr($messageString, (strpos($messageString, "\r\n\r\n") + 4));

        $headers  = self::parseHeaders($headerString);
        $boundary = null;
        $parts    = [];

        foreach ($headers as $header) {
            if ($header->hasParameter('boundary')) {
                $boundary = $header->getParameter('boundary');
            }
        }

        $partStrings = self::parseBody($bodyString, $boundary);

        foreach ($partStrings as $partString) {
            $parts[] = self::parsePart($partString);
        }

        $message = new self();

        if (!empty($headers)) {
            $message->addHeaders($headers);
        }
        if (!empty($parts)) {
            $message->addParts($parts);
        }

        return $message;
    }

    /**
     * Parse form data
     *
     * @param  string $formString
     * @return array
     */
    public static function parseForm($formString)
    {
        $form     = self::parseMessage($formString);
        $formData = [];

        foreach ($form->getParts() as $part) {
            if ($part->hasHeader('Content-Disposition')) {
                $disposition = $part->getHeader('Content-Disposition');
                if (($disposition->getValue() == 'form-data') && ($disposition->hasParameter('name'))) {
                    $formData[$disposition->getParameter('name')] = $part->getContents();
                }
            }
        }

        return $formData;
    }

    /**
     * Parse message header string
     *
     * @param  string $headerString
     * @return array
     */
    public static function parseHeaders($headerString)
    {
        $headers = [];
        $matches = [];
        preg_match_all('/[a-zA-Z-]+:/', $headerString, $matches, PREG_OFFSET_CAPTURE);

        if (isset($matches[0]) && (count($matches[0]) > 0)) {
            $length = count($matches[0]);
            for ($i = 0; $i < $length; $i++) {
                if (isset($matches[0][$i + 1][1])) {
                    $start  = $matches[0][$i][1] + strlen($matches[0][$i][0]);
                    $offset = $matches[0][$i + 1][1];
                    $value  = substr($headerString, 0, $offset);
                    $value  = trim(substr($value, $start));
                } else {
                    $start  = strpos($headerString, $matches[0][$i][0]) + strlen($matches[0][$i][0]);
                    $value  = substr($headerString, $start);
                }
                $headers[] = Header::parse($matches[0][$i][0] . ' ' . trim($value));
            }
        }

        return $headers;
    }

    /**
     * Parse message body string
     *
     * @param  string $bodyString
     * @param  string $boundary
     * @return array
     */
    public static function parseBody($bodyString, $boundary = null)
    {
        if (strpos($bodyString, '--' . $boundary) !== false) {
            $parts = explode('--' . $boundary, $bodyString);
            if ((strpos($bodyString, '--' . $boundary) > 0) && isset($parts[0])) {
                unset($parts[0]);
            }
        } else {
            $parts = [$bodyString];
        }

        $parts = array_values(array_filter(array_map('trim', $parts), function ($value) {
            return (!empty($value) && ($value != '--'));
        }));

        return $parts;
    }

    /**
     * Parse message part string
     *
     * @param  string $partString
     * @return Part
     */
    public static function parsePart($partString)
    {
        $headers = [];

        if (strpos($partString, "\r\n\r\n") !== false) {
            $headerString = substr($partString, 0, strpos($partString, "\r\n\r\n"));
            $bodyString   = trim(substr($partString, (strpos($partString, "\r\n\r\n") + 4)));
            $headers      = self::parseHeaders($headerString);
        } else {
            $bodyString   = trim($partString);
        }

        $part = new Part();

        if (!empty($headers)) {
            $part->addHeaders($headers);
        }

        if (!empty($bodyString)) {
            $encoding = null;
            $isFile   = (($part->hasHeader('Content-Disposition')) &&
                ($part->getHeader('Content-Disposition')->isAttachment()));
            if ($part->hasHeader('Content-Transfer-Encoding')) {
                $encodingHeader = strtolower($part->getHeader('Content-Transfer-Encoding')->getValue());
                if ($encodingHeader == 'base64') {
                    $encoding = Body::BASE64;
                } else if ($encodingHeader == 'quoted-printable') {
                    $encoding = Body::QUOTED;
                }
            }
            $body = new Body($bodyString, $encoding);
            if (null !== $encoding) {
                $body->setAsEncoded(true);
            }
            if ($isFile) {
                $body->setAsFile(true);
            }
            $part->setBody($body);
        }

        return $part;
    }

}