<?php
/* Digraph CMS | https://github.com/digraphcms/digraph | MIT License */
declare(strict_types=1);
namespace Digraph\CMS\Forms\Fields;

use PHPUnit\Framework\TestCase;

class SlugTest extends TestCase
{
    public function testTrim()
    {
        $field = new Slug('test');
        $field->value('/foo//');
        $this->assertEquals('foo', $field->value());
    }

    public function testValidation()
    {
        $field = new Slug('test');
        $field->value('foo');
        $this->assertTrue($field->validate());
    }
}
