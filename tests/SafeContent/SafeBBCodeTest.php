<?php

namespace DigraphCMS\SafeContent;

use PHPUnit\Framework\TestCase;

class SafeBBCodeTest extends TestCase
{
    public function testParse()
    {
        // test finding strings
        $this->assertEquals(
            '<div class=\'safe-bbcode-content\'>Test <a href="https://example.com" target="_blank">https://example.com</a></div>',
            SafeBBCode::parse('Test https://example.com')
        );
        $this->assertEquals(
            '<div class=\'safe-bbcode-content\'>Test <a href="https://example.com" target="_blank">https://example.com</a> <a href="https://example2.com" target="_blank">https://example2.com</a></div>',
            SafeBBCode::parse('Test https://example.com https://example2.com')
        );
    }

    public function testParseMultiline()
    {
        // test that it works with multiline input
        $this->assertEquals(
            '<div class=\'safe-bbcode-content\'>Test <a href="https://example.com" target="_blank">https://example.com</a><br>Test <a href="https://example2.com" target="_blank">https://example2.com</a></div>',
            SafeBBCode::parse("Test https://example.com\nTest https://example2.com")
        );
    }
}
