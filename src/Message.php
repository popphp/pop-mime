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

use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;

/**
 * MIME message class
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    2.0.2
 */
class Message extends Part
{

    /**
     * Parse message
     *
     * @param  string $messageString
     * @return Message
     */
    public static function parseMessage(string $messageString): Message
    {
        $headerString = substr($messageString, 0, strpos($messageString, "\r\n\r\n"));
        $bodyString   = substr($messageString, (strpos($messageString, "\r\n\r\n") + 4));

        $headers  = self::parseHeaders($headerString);
        $boundary = null;
        $parts    = [];

        foreach ($headers as $header) {
            foreach ($header->getValues() as $headerValue) {
                if ($headerValue->hasParameter('boundary')) {
                    $boundary = $headerValue->getParameter('boundary');
                    break;
                }
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
    public static function parseForm(string $formString): array
    {
        $form     = self::parseMessage($formString);
        $formData = [];

        foreach ($form->getParts() as $part) {
            if (($part->hasHeader('Content-Disposition')) && (count($part->getHeader('Content-Disposition')->getValues()) == 1)) {
                $disposition = $part->getHeader('Content-Disposition');
                if (($disposition->hasValue('form-data')) && ($disposition->getValue(0)->hasParameter('name'))) {
                    $name     = $disposition->getValue(0)->getParameter('name');
                    $contents = $part->getContents();
                    $filename = ($disposition->getValue(0)->hasParameter('filename')) ? $disposition->getValue(0)->getParameter('filename') : null;

                    if (str_ends_with($name, '[]')) {
                        $name = substr($name, 0, -2);
                        if (!isset($formData[$name])) {
                            $formData[$name] = [];
                        }
                        $formData[$name][] = $contents;
                    } else {
                        if ($filename !== null) {
                            $formData[$name] = [
                                'filename' => $filename,
                                'contents' => $contents
                            ];
                        } else {
                            $formData[$name] = $contents;
                        }
                    }
                }
            }
        }

        return $formData;
    }

    /**
     * Create multipart form object
     *
     * @param  array $fields
     * @return Message
     */
    public static function createForm(array $fields = []): Message
    {
        $message = new self();
        $header  = new Header('Content-Type', new Header\Value('multipart/form-data', null, ['boundary' => $message->generateBoundary()]));
        $message->addHeader($header);

        if (!empty($fields)) {
            foreach ($fields as $name => $value) {
                if (is_array($value)) {
                    // Is file
                    if (isset($value['filename'])) {
                        $parameters   = ['name' => $name];
                        $contentType  = null;
                        $fileContents = null;

                        foreach ($value as $key => $val) {
                            $key = strtolower($key);
                            if ($key == 'filename') {
                                $parameters['filename'] = basename($val);
                            }
                            if (($key == 'content-type') || ($key == 'contenttype') ||
                                ($key == 'mime-type') || ($key == 'mimetype') || ($key == 'mime')) {
                                $contentType = $val;
                            }
                        }

                        if (isset($value['contents'])) {
                            $fileContents = $value['contents'];
                        } else if (file_exists($value['filename'])) {
                            $fileContents = file_get_contents($value['filename']);
                        }

                        $fieldPart = new Part(new Header('Content-Disposition', new Header\Value('form-data', null, $parameters)));
                        if ($contentType !== null) {
                            $fieldPart->addHeader('Content-Type', $contentType);
                        }
                        $fieldPart->setBody(new Body($fileContents));
                        $message->addPart($fieldPart);
                    } else {
                        foreach ($value as $val) {
                            $fieldPart = new Part(
                                new Header('Content-Disposition', new Header\Value('form-data', null, ['name' => $name . '[]']))
                            );
                            $fieldPart->setBody(new Body($val, Body::RAW_URL));
                            $message->addPart($fieldPart);
                        }
                    }
                } else {
                    $fieldPart = new Part(new Header('Content-Disposition', new Header\Value('form-data', null, ['name' => $name])));
                    $fieldPart->setBody(new Body($value, Body::RAW_URL));
                    $message->addPart($fieldPart);
                }
            }
        }

        return $message;
    }

    /**
     * Parse message header string
     *
     * @param  string $headerString
     * @return array
     */
    public static function parseHeaders(string $headerString): array
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
     * @param  string  $bodyString
     * @param  ?string $boundary
     * @return array
     */
    public static function parseBody(string $bodyString, ?string $boundary = null): array
    {
        if (str_contains($bodyString, '--' . $boundary)) {
            $parts = explode('--' . $boundary, $bodyString);
            if ((strpos($bodyString, '--' . $boundary) > 0) && isset($parts[0])) {
                unset($parts[0]);
            }
        } else {
            $parts = [$bodyString];
        }

        return array_values(array_filter(array_map('trim', $parts), function ($value) {
            return (!empty($value) && ($value != '--'));
        }));
    }

    /**
     * Parse message part string
     *
     * @param  string $partString
     * @return Part|array
     */
    public static function parsePart(string $partString): Part|array
    {
        $headers = [];

        if (str_contains($partString, "\r\n\r\n")) {
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

        $boundary = null;
        foreach ($part->getHeaders() as $header) {
            foreach ($header->getValues() as $headerValue) {
                if ($headerValue->hasParameter('boundary')) {
                    $boundary = $headerValue->getParameter('boundary');
                    break;
                }
            }
        }

        if (!empty($bodyString)) {
            if ($boundary !== null) {
                $subPartStrings = self::parseBody($bodyString, $boundary);
                $subParts       = [];

                foreach ($subPartStrings as $subPartString) {
                    $subParts[] = self::parsePart($subPartString);
                }
                return $subParts;
            } else {
                $encoding = null;
                $isFile   = (($part->hasHeader('Content-Disposition')) &&
                    ($part->getHeader('Content-Disposition')->isAttachment()));
                $isForm   = (($part->hasHeader('Content-Disposition')) &&
                    ($part->getHeader('Content-Disposition')->hasValue('form-data')));
                if ($part->hasHeader('Content-Transfer-Encoding') && (count($part->getHeader('Content-Transfer-Encoding')->getValues()) == 1)) {
                    $encodingHeader = strtolower($part->getHeader('Content-Transfer-Encoding')->getValue(0));
                    if ($encodingHeader == 'base64') {
                        $encoding = Body::BASE64;
                    } else if ($encodingHeader == 'quoted-printable') {
                        $encoding = Body::QUOTED;
                    }
                } else if ($isForm) {
                    $encoding = Body::RAW_URL;
                }
                $body = new Body($bodyString, $encoding);
                if ($encoding !== null) {
                    $body->setAsEncoded(true);
                }
                if ($isFile) {
                    $body->setAsFile(true);
                }
                $part->setBody($body);
            }
        }

        return $part;
    }

}
