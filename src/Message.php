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
namespace Pop\Mime;

use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;

/**
 * MIME message class
 *
 * @category   Pop
 * @package    Pop\Mime
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Message extends Part
{

    /**
     * Set the Message-ID header
     *
     * @param  ?string $id
     * @param  ?string $domain
     * @return Message
     */
    public function setMessageId(?string $id = null, ?string $domain = null): Message
    {
        $this->addHeader('Message-ID', $id ?? $this->generateId($domain));
        return $this;
    }

    /**
     * Parse message
     *
     * @param  string $messageString
     * @return Message
     */
    public static function parseMessage(string $messageString): Message
    {
        $splitPos = strpos($messageString, "\r\n\r\n");

        // Without a header/body delimiter, there are no headers to parse
        // and the entire string is taken as the body
        if ($splitPos !== false) {
            $headerString = substr($messageString, 0, $splitPos);
            $bodyString   = substr($messageString, $splitPos + 4);
        } else {
            $headerString = '';
            $bodyString   = $messageString;
        }

        $headers  = self::parseHeaders($headerString);
        $boundary = null;

        foreach ($headers as $header) {
            foreach ($header->getValues() as $headerValue) {
                if ($headerValue->hasParameter('boundary')) {
                    $boundary = $headerValue->getParameter('boundary');
                    break;
                }
            }
        }

        $message = new self();

        if (!empty($headers)) {
            $message->addHeaders($headers);
        }

        // With a boundary, the body is a multipart container of nested parts.
        // Without one, this is a single, non-multipart message and the body
        // belongs directly on the message itself - mirrors parsePart()'s
        // leaf/container branch below.
        if ($boundary !== null) {
            $parts = [];
            foreach (self::parseBody($bodyString, $boundary) as $partString) {
                $parts[] = self::parsePart($partString);
            }
            if (!empty($parts)) {
                $message->addParts($parts);
            }
        } else if (trim($bodyString) !== '') {
            self::buildLeafBody($message, trim($bodyString));
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
                            $fieldPart->setBody(new Body($val, Body\Encoding::RAW_URL));
                            $message->addPart($fieldPart);
                        }
                    }
                } else {
                    $fieldPart = new Part(new Header('Content-Disposition', new Header\Value('form-data', null, ['name' => $name])));
                    $fieldPart->setBody(new Body($value, Body\Encoding::RAW_URL));
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
        $headers  = [];
        $unfolded = Header\Lexer::unfold($headerString);
        $lines    = explode("\r\n", $unfolded);

        $currentName  = null;
        $currentLines = [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $colonToken = Header\Lexer::findTopLevelDelimiter($line, ':');

            if ($colonToken !== null) {
                if ($currentName !== null) {
                    $headers[] = Header::parse(implode("\r\n", $currentLines));
                }
                $currentName  = trim(substr($line, 0, $colonToken->start));
                $currentLines = [$line];
            } else if ($currentName !== null) {
                $currentLines[] = $line;
            }
        }

        if ($currentName !== null) {
            $headers[] = Header::parse(implode("\r\n", $currentLines));
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
        $needle   = '--' . $boundary;
        $splitPos = strpos($bodyString, $needle);

        if ($splitPos !== false) {
            $parts = explode($needle, $bodyString);
            if ($splitPos > 0) {
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

        $splitPos = strpos($partString, "\r\n\r\n");

        if ($splitPos !== false) {
            $headerString = substr($partString, 0, $splitPos);
            $bodyString   = trim(substr($partString, $splitPos + 4));
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
                self::buildLeafBody($part, $bodyString);
            }
        }

        return $part;
    }

    /**
     * Build and set a leaf part's body from decoded content, inferring the
     * encoding from Content-Transfer-Encoding / form-data Content-Disposition
     * headers already set on the part
     *
     * @param  Part   $part
     * @param  string $bodyString
     * @return void
     */
    protected static function buildLeafBody(Part $part, string $bodyString): void
    {
        $encoding = null;
        $isFile   = (($part->hasHeader('Content-Disposition')) &&
            ($part->getHeader('Content-Disposition')->isAttachment()));
        $isForm   = (($part->hasHeader('Content-Disposition')) &&
            ($part->getHeader('Content-Disposition')->hasValue('form-data')));
        if ($part->hasHeader('Content-Transfer-Encoding') && (count($part->getHeader('Content-Transfer-Encoding')->getValues()) == 1)) {
            $encoding = Body\Encoding::fromHeaderValue((string)$part->getHeader('Content-Transfer-Encoding')->getValue(0));
        } else if ($isForm) {
            $encoding = Body\Encoding::RAW_URL;
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
