<?php

namespace DigraphCMS\SafeContent;

use DigraphCMS\UI\Format;
use PHPUnit\Framework\TestCase;

class SafeBBCodeHtmlParserTest extends TestCase
{
    public function testParse()
    {
        // test finding strings
        $this->assertEquals(
            'Test <a href="https://example.com" target="_blank">https://example.com</a>',
            SafeBBCodeHtmlParser::parse('Test https://example.com')
        );
        $this->assertEquals(
            'Test <a href="https://example.com" target="_blank">https://example.com</a> <a href="https://example2.com" target="_blank">https://example2.com</a>',
            SafeBBCodeHtmlParser::parse('Test https://example.com https://example2.com')
        );
        $this->assertEquals(
            'Test ' . Format::base64obfuscate('<a href="mailto:test@example.com">test@example.com</a>'),
            SafeBBCodeHtmlParser::parse('Test test@example.com')
        );
        $this->assertEquals(
            'Test ' . Format::base64obfuscate('<a href="mailto:test@example.com">test@example.com</a>') . ' ' . Format::base64obfuscate('<a href="mailto:test2@example.com">test2@example.com</a>'),
            SafeBBCodeHtmlParser::parse('Test test@example.com test2@example.com')
        );
        // test with more complex example URLs
        $this->assertEquals(
            'Test <a href="https://example.com/foo/bar" target="_blank">https://example.com/foo/bar</a>',
            SafeBBCodeHtmlParser::parse('Test https://example.com/foo/bar')
        );
        $this->assertEquals(
            'Test <a href="https://example.com/foo/bar/" target="_blank">https://example.com/foo/bar/</a> URL',
            SafeBBCodeHtmlParser::parse('Test https://example.com/foo/bar/ URL')
        );
        // test that it doesn't alter existing links
        $this->assertEquals(
            'Test <a href="https://example.com" target="_blank">https://example.com</a> <a href="https://example2.com" target="_blank">https://example2.com</a>',
            SafeBBCodeHtmlParser::parse('Test <a href="https://example.com" target="_blank">https://example.com</a> <a href="https://example2.com" target="_blank">https://example2.com</a>')
        );
    }
}
