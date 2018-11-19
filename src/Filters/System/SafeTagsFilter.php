<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

class SafeTagsFilter extends AbstractSystemFilter
{
    const TAGS_PROVIDED_STRING = '[link], [img], [b], [i], [u], [s], [url], [quote], [code]';

    public function tag_b($context, $text, $args)
    {
        return "<strong>$text</strong>";
    }

    public function tag_i($context, $text, $args)
    {
        return "<em>$text</em>";
    }

    public function tag_u($context, $text, $args)
    {
        return "<ins>$text</ins>";
    }

    public function tag_s($context, $text, $args)
    {
        return "<del>$text</del>";
    }

    public function tag_url($context, $text, $args)
    {
        return "<a href=\"$text\">$text</a>";
    }

    public function tag_quote($context, $text, $args)
    {
        return "<blockquote>$text</blockquote>";
    }

    public function tag_code($context, $text, $args)
    {
        if (!$text) {
            return false;
        }
        $style = '';
        if ($lang = @$args['lang']) {
            $lang = preg_replace('/[^a-z0-9]/', '', $lang);
            $style = ' style="language-'.$lang.'"';
        }
        $text = trim($text, "\r\n");
        $text = "<code$style>".htmlspecialchars($text)."</code>";
        if (preg_match('[\r\n]', $text)) {
            $text = "<pre>$text</pre>";
        }
        return $text;
    }

    public function tag_link($context, $text, $args)
    {
        $noun = $this->cms->read($context);
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

    /**
     * Including this lets us use the drag/drop file tags, but we short-circuit
     * and don't let this version handle non-image files.
     */
    public function tag_file($context, $text, $args)
    {
        return $this->tag_img($context, $text, $args);
    }

    public function tag_img($context, $text, $args)
    {
        $noun = $this->cms->read($context);
        if (!$noun) {
            return false;
        }
        //use noun's file tag handler, if it exists
        if (method_exists($noun, 'tagImg')) {
            return $noun->tagImg($args);
        }
        //default file handler
        $fs = $this->cms->helper('filestore');
        $file = $fs->get($noun, $args['id']);
        if (!$file) {
            return false;
        }
        $file = array_pop($file);
        //return false for non-image files
        if (!$file->isImage()) {
            return false;
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
