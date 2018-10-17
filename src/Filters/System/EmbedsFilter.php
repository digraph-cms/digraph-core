<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

class EmbedsFilter extends AbstractSystemFilter
{
    public function tag_embed($context, $text, $args)
    {
        $noun = $this->cms->read($context);
        if (!$noun) {
            return false;
        }
        if (method_exists($noun, 'tagEmbed')) {
            return $noun->tagEmbed($args);
        }
    }
}
