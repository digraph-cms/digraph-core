<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

class EmbedsFilter extends AbstractSystemFilter
{
    const TAGS_PROVIDED_STRING = '[embed], [file]';

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

    public function tag_file($context, $text, $args)
    {
        $noun = $this->cms->read($context);
        if (!$noun) {
            return false;
        }
        //use noun's file tag handler, if it exists
        if (method_exists($noun, 'tag_file')) {
            return $noun->tag_file($args);
        }
        //default file handler
        $fs = $this->cms->helper('filestore');
        $file = $fs->get($noun, $args['id']);
        if (!$file) {
            return false;
        }
        $file = array_pop($file);
        //return metacard for non-image files and mode=card
        if (@$args['mode'] == 'card' || !$file->isImage()) {
            return $file->metaCard();
        }
        //return img tag otherwise
        $preset = @$args['preset']?$args['preset']:'tag-embed';
        $url = $file->imageUrl($preset);
        $attr = [];
        $attr['src'] = "src=\"$url\"";
        $attr['class'] = "class=\"digraph-image-embed digraph-image-embed_$preset\"";
        return "<img ".implode(' ', $attr).">";
    }
}
