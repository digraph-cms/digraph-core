<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

class LinksFilter extends AbstractSystemFilter
{
    public function tag_link($primary, $text, $args)
    {
        $noun = $this->cms->read($primary);
        if (!$noun) {
            return false;
        }
        if (method_exists($noun, 'tagLink')) {
            $link = $noun->tagLink($args);
        } else {
            $link = $noun->url(@$args['verb'])->html();
        }
        if ($text) {
            $link->content = $text;
        }
        return "$link";
    }
}
