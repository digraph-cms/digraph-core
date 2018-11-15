<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

class TwigFilter extends AbstractSystemFilter
{
    const TAGS_PROVIDED_STRING = '[twig]';

    public function tag_ml_twig($context, $text, $args)
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
