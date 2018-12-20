<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\BBCode;

class BBCodeBasicFilter extends AbstractBBCodeFilter
{
    const TEMPLATEPREFIX = 'bbcode/basic/';
    const HTMLCOLORS = ["aliceblue","antiquewhite","aqua","aquamarine","azure",
    "beige","bisque","black","blanchedalmond","blue","blueviolet","brown",
    "burlywood","cadetblue","chartreuse","chocolate","coral","cornflowerblue",
    "cornsilk","crimson","cyan","darkblue","darkcyan","darkgoldenrod",
    "darkgray","darkgrey","darkgreen","darkkhaki","darkmagenta",
    "darkolivegreen","darkorange","darkorchid","darkred","darksalmon",
    "darkseagreen","darkslateblue","darkslategray","darkslategrey",
    "darkturquoise","darkviolet","deeppink","deepskyblue","dimgray","dimgrey",
    "dodgerblue","firebrick","floralwhite","forestgreen","fuchsia","gainsboro",
    "ghostwhite","gold","goldenrod","gray","grey","green","greenyellow",
    "honeydew","hotpink","indianred ","indigo ","ivory","khaki","lavender",
    "lavenderblush","lawngreen","lemonchiffon","lightblue","lightcoral",
    "lightcyan","lightgoldenrodyellow","lightgray","lightgrey","lightgreen",
    "lightpink","lightsalmon","lightseagreen","lightskyblue","lightslategray",
    "lightslategrey","lightsteelblue","lightyellow","lime","limegreen","linen",
    "magenta","maroon","mediumaquamarine","mediumblue","mediumorchid",
    "mediumpurple","mediumseagreen","mediumslateblue","mediumspringgreen",
    "mediumturquoise","mediumvioletred","midnightblue","mintcream","mistyrose",
    "moccasin","navajowhite","navy","oldlace","olive","olivedrab","orange",
    "orangered","orchid","palegoldenrod","palegreen","paleturquoise",
    "palevioletred","papayawhip","peachpuff","peru","pink","plum","powderblue",
    "purple","rebeccapurple","red","rosybrown","royalblue","saddlebrown",
    "salmon","sandybrown","seagreen","seashell","sienna","silver","skyblue",
    "slateblue","slategray","slategrey","snow","springgreen","steelblue","tan",
    "teal","thistle","tomato","turquoise","violet","wheat","white","whitesmoke",
    "yellow","yellowgreen"];

    public function tag_b($context, $text, $args)
    {
        return "<strong>$text</strong>";
    }

    public function tag_style($context, $text, $args)
    {
        $css = [];
        foreach ($args as $key => $value) {
            switch ($key) {
                //color must be valid hex, rgb/a, or color nickname
                case 'color':
                    if (in_array(strtolower($value), static::HTMLCOLORS)) {
                        //valid color name
                        $css['color'] = 'color:'.$value;
                    } elseif (preg_match('/^\#([0-9a-f]{3,3}){1,2}$/i', $value)) {
                        // valid hex code
                        $css['color'] = 'color:'.$value;
                    } elseif (preg_match('/^([0-9a-f]{3,3}){1,2}$/i', $value)) {
                        // implicit hex code
                        $css['color'] = 'color:#'.$value;
                    } elseif (preg_match('/^rgb([0-9]{1,3}, ?[0-9]{1,3}, ?[0-9]{1,3})$/')) {
                        // valid rgb code
                        $css['color'] = 'color:'.$value;
                    } elseif (preg_match('/^[0-9]{1,3}, ?[0-9]{1,3}, ?[0-9]{1,3}$/')) {
                        // implicit rgb code
                        $css['color'] = 'color:rgb('.$value.')';
                    } elseif (preg_match('/^rgba([0-9]{1,3}, ?[0-9]{1,3}, ?[0-9]{1,3}, ?[0-9]{1,3})$/')) {
                        // valid rgb code
                        $css['color'] = 'color:'.$value;
                    } elseif (preg_match('/^[0-9]{1,3}, ?[0-9]{1,3}, ?[0-9]{1,3}, ?[0-9]{1,3}$/')) {
                        // implicit rgb code
                        $css['color'] = 'color:rgba('.$value.')';
                    }
                    break;
                // size must be a valid number and unit, or a number to use as a percentage
                case 'size':
                    if (preg_match('/^[0-9]+(\.[0-9]+)?(cm|mm|in|px|pt|pc|em|ex|ch|rem|vw|vh|vmin|vmax|%)$/', $value)) {
                        $css['size'] = 'font-size:'.$value;
                    } elseif (preg_match('/^[0-9]+(\.[0-9]+)?$/', $value)) {
                        $css['size'] = 'font-size:'.$value.'%';
                    }
                    break;
            }
        }
        if ($css) {
            return "<span style=\"".implode(';', $css)."\">$text</span>";
        } else {
            return $text;
        }
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
        $url = $text;
        if ($args['equals']) {
            //if a url is specified in equals, use it
            $url = $args['equals'];
            //if url is relative, try to parse it, and use parsed version
            if (!preg_match('/^.+:\/\//', $url)) {
                if ($parsed = $this->cms->helper('urls')->parse($url)) {
                    $url = $parsed;
                    if (!$text) {
                        $text = $url['text'];
                    }
                }
            }
        } else {
            //otherwise use context to generate url/text as needed
            if ($context = $this->cms->read($context)) {
                $url = $context->url($args['verb']);
                if (!$text) {
                    $text = $url['text'];
                }
            }
        }
        //return url and text as html
        return "<a href=\"$url\">$text</a>";
    }

    public function tag_quote($context, $text, $args)
    {
        if (($author = @$args['equals']) || ($author = @$args['author'])) {
            if ($r = $this->cms->helper('users')->search($author)) {
                $author = array_shift($r);
            }
        }
        $out = "<blockquote>";
        $out .= $text;
        if ($author) {
            $out .= "<div class=\"author\">$author</div>";
        }
        $out .= "</blockquote>";
        return $out;
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
        if (method_exists($noun, 'tag_link')) {
            return $noun->tag_link($text, $args);
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
        return $this->tag_image($context, $text, $args);
    }

    public function tag_img($context, $text, $args)
    {
        $noun = $this->cms->read($context);
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
