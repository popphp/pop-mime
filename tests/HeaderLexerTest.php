<?php

namespace Pop\Mime\Test;

use Pop\Mime\Part\Header\Lexer;
use PHPUnit\Framework\TestCase;

class HeaderLexerTest extends TestCase
{

    public function testTokenizesAtomsAndDelimiters()
    {
        $tokens = (new Lexer('attachment; filename=file.jpg'))->tokenize();

        $this->assertEquals(Lexer::ATOM, $tokens[0]->type);
        $this->assertEquals('attachment', $tokens[0]->value);
        $this->assertEquals(Lexer::DELIMITER, $tokens[1]->type);
        $this->assertEquals(';', $tokens[1]->value);
        $this->assertEquals(Lexer::FWS, $tokens[2]->type);
        $this->assertEquals(Lexer::ATOM, $tokens[3]->type);
        $this->assertEquals('filename', $tokens[3]->value);
        $this->assertEquals(Lexer::DELIMITER, $tokens[4]->type);
        $this->assertEquals('=', $tokens[4]->value);
        $this->assertEquals(Lexer::ATOM, $tokens[5]->type);
        $this->assertEquals('file.jpg', $tokens[5]->value);
    }

    public function testDelimiterInsideQuotedStringIsNotATopLevelDelimiter()
    {
        $tokens = (new Lexer('filename="invoice; final.pdf"'))->tokenize();

        $this->assertCount(3, $tokens);
        $this->assertEquals(Lexer::ATOM, $tokens[0]->type);
        $this->assertEquals('filename', $tokens[0]->value);
        $this->assertEquals(Lexer::DELIMITER, $tokens[1]->type);
        $this->assertEquals('=', $tokens[1]->value);
        $this->assertEquals(Lexer::QUOTED_STRING, $tokens[2]->type);
        $this->assertEquals('invoice; final.pdf', $tokens[2]->value);
    }

    public function testBackslashEscapedQuoteInsideQuotedString()
    {
        $tokens = (new Lexer('username="jane \"the boss\" doe"'))->tokenize();

        $this->assertEquals(Lexer::QUOTED_STRING, $tokens[2]->type);
        $this->assertEquals('jane "the boss" doe', $tokens[2]->value);
    }

    public function testNestedCommentsAreDiscarded()
    {
        $tokens = (new Lexer('Bearer (outer (inner) still outer) token123'))->tokenize();
        $values = array_values(array_map(
            fn($token) => $token->value,
            array_filter($tokens, fn($token) => $token->type !== Lexer::FWS)
        ));

        $this->assertEquals(['Bearer', 'token123'], $values);
    }

    public function testObsFoldWhitespaceCollapsesToSingleSpace()
    {
        $tokens = (new Lexer("foo;\r\n\tbar"))->tokenize();

        $this->assertEquals(Lexer::FWS, $tokens[2]->type);
        $this->assertEquals(' ', $tokens[2]->value);
    }

    public function testUnterminatedQuotedStringDegradesGracefully()
    {
        $tokens = (new Lexer('filename="unterminated.pdf'))->tokenize();

        $this->assertEquals(Lexer::QUOTED_STRING, $tokens[2]->type);
        $this->assertEquals('unterminated.pdf', $tokens[2]->value);
    }

    public function testUnbalancedCommentDegradesGracefully()
    {
        $tokens = (new Lexer('token (unterminated comment'))->tokenize();

        $this->assertCount(2, $tokens);
        $this->assertEquals('token', $tokens[0]->value);
        $this->assertEquals(Lexer::FWS, $tokens[1]->type);
    }

    public function testUnfoldCollapsesObsFoldToSingleSpace()
    {
        $unfolded = Lexer::unfold("Content-Disposition: form-data; name=image;\r\n\tfilename=photo.jpg");
        $this->assertEquals('Content-Disposition: form-data; name=image; filename=photo.jpg', $unfolded);
    }

    public function testUnfoldLeavesRegularCrlfIntact()
    {
        $unfolded = Lexer::unfold("Set-Cookie: 123456\r\nSet-Cookie: 987654");
        $this->assertEquals("Set-Cookie: 123456\r\nSet-Cookie: 987654", $unfolded);
    }

    public function testFindTopLevelDelimiterSkipsColonInsideQuotedString()
    {
        $token = Lexer::findTopLevelDelimiter('Authorization: Bearer token; note="ratio 3:1"', ':');
        $this->assertNotNull($token);
        $this->assertEquals(13, $token->start);
    }

    public function testFindTopLevelDelimiterReturnsNullWhenAbsent()
    {
        $token = Lexer::findTopLevelDelimiter('no colon here', ':');
        $this->assertNull($token);
    }

    public function testTokenizesAngleBracketsAsDelimiters()
    {
        $tokens = (new Lexer('<john@example.com>'))->tokenize();

        $this->assertEquals(Lexer::DELIMITER, $tokens[0]->type);
        $this->assertEquals('<', $tokens[0]->value);
        $this->assertEquals(Lexer::ATOM, $tokens[1]->type);
        $this->assertEquals('john@example.com', $tokens[1]->value);
        $this->assertEquals(Lexer::DELIMITER, $tokens[2]->type);
        $this->assertEquals('>', $tokens[2]->value);
    }

    public function testAngleBracketsInsideQuotedStringAreNotTopLevelDelimiters()
    {
        $tokens = (new Lexer('"a <weird> name"'))->tokenize();

        $this->assertCount(1, $tokens);
        $this->assertEquals(Lexer::QUOTED_STRING, $tokens[0]->type);
        $this->assertEquals('a <weird> name', $tokens[0]->value);
    }

}
