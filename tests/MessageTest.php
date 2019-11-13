<?php

namespace Pop\Mime\Test;

use Pop\Mime\Message;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{

    public function testConstructor()
    {
        $message = new Message();
        $this->assertInstanceOf('Pop\Mime\Message', $message);
    }

}