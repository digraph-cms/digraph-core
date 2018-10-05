<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

/**
 * This abstract filter locates and processes Digraph system tags, and is
 * meant to be extended to build all the system tag filters.
 */
class LinksFilter extends AbstractSystemFilter
{
    public function tag_link($tag, $primary, $text, $args)
    {
        $url = $this->cms->helper('urls')->parse($primary);
        if (!$url) {
            return false;
        }
        $link = $url->html();
        if ($text) {
            $link->content = $text;
        }
        return "$link";
    }
}
