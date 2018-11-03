<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

class TwigFilter extends AbstractSystemFilter
{
    public function tag_twig($context, $text, $args)
    {
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