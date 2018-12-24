<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\BBCode;

class BBCodeAdvancedFilter extends AbstractBBCodeFilter
{
    const TEMPLATEPREFIX = '_bbcode/advanced/';

    public function tag_embed($context, $text, $args)
    {
        $noun = $this->cms->read($context);
        if (!$noun) {
            return false;
        }
        if (method_exists($noun, 'tag_embed')) {
            return $noun->tag_embed($text, $args);
        }
    }
}
