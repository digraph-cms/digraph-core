<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

class TemplatesFilter extends AbstractSystemFilter
{
    const TAGS_PROVIDED_STRING = '[anytemplate], [block], [allblocks]';

    public function tag_block($context, $text, $args)
    {
        return $this->cms->helper('blocks')->block($context);
    }

    public function tag_allblocks($context, $text, $args)
    {
        $out = '<div class="digraph-blocks">';
        $out += '</div>';
        return $out;
    }

    public function tag_anytemplate($context, $text, $args)
    {
        if (preg_match('/[^a-z0-9\-_]/i', $text)) {
            return '[invalid character in template]';
        }
        $t = $this->cms->helper('templates');
        $template = $text;
        if (!$t->exists($template)) {
            return false;
        }
        $fields = $args;
        $fields['noun'] = $this->cms->read($context);
        if (!$fields['noun']) {
            return false;
        }
        return $t->render(
            $template,
            $fields
        );
    }
}
