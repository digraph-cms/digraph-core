<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

class TemplatesFilter extends AbstractSystemFilter
{
    const TAGS_PROVIDED_STRING = '[template], [block], [allblocks]';

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

    public function tag_template($context, $text, $args)
    {
        $t = $this->cms->helper('templates');
        $template = 'tags/'.$text;
        if (!$t->exists($template)) {
            return "[template $template not found]";
        }
        $fields = $args;
        $fields['noun'] = $this->cms->read($context);
        if (!$fields['noun']) {
            return "[noun $context not found]";
        }
        return $t->render(
            $template,
            $fields
        );
    }
}
