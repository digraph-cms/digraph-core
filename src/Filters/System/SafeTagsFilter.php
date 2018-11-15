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

    public function tag_ml_quote($context, $text, $args)
    {
        return "<blockquote>$text</blockquote>";
    }

    /**
     * First search for inline code, and make a code tag without a PRE tag
     */
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
        $text = trim($text);
        return "<code$style>".htmlspecialchars($text)."</code>";
    }

    /**
     * Then search for multiline code, and trim its output of extra newlines and
     * wrap the final out put in a pre tag as well
     */
    public function tag_ml_code($context, $text, $args)
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
        return "<pre><code$style>".htmlspecialchars($text)."</code></pre>";
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
