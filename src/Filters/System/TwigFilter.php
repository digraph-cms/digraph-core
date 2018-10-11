<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

class TwigFilter extends AbstractSystemFilter
{
    public function tag_twig($primary, $text, $args)
    {
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
