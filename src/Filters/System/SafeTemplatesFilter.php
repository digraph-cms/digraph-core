<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

class SafeTemplatesFilter extends AbstractSystemFilter
{
    const TAGS_PROVIDED_STRING = '[template]';

    public function tag($tag, $context, $text, $args)
    {
        return $this->tag_template($context, $tag, $args);
    }

    public function tag_template($context, $text, $args)
    {
        if (preg_match('/[^a-z0-9\-_]/i', $text)) {
            return false;
        }
        $t = $this->cms->helper('templates');
        $template = 'tags/safe/'.$text;
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
