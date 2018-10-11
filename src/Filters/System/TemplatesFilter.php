<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

class TemplatesFilter extends AbstractSystemFilter
{
    public function tag_template($primary, $text, $args)
    {
        $t = $this->cms->helper('templates');
        $template = 'tags/'.$text;
        if (!$t->exists($template)) {
            return "[template $template not found]";
        }
        $fields = $args;
        $fields['noun'] = $this->cms->read($primary);
        if (!$fields['noun']) {
            return "[noun $primary not found]";
        }
        return $t->render(
            $template,
            $fields
        );
    }
}
