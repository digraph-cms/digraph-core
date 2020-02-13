<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\BBCode;

class BBCodeBasicFilter extends AbstractBBCodeFilter
{
    const TEMPLATEPREFIX = '_bbcode/basic/';
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

    public function tag_email($context, $text, $args)
    {
        $s = $this->cms->helper('strings');
        if (!($email = @$args['addr'])) {
            $email = $text;
        }
        $href = $s->allHtmlEntities('mailto:'.$email);
        $text = $s->allHtmlEntities($text);
        $out = '<a href="'.$href.'">'.$text.'</a>';
        return $s->obfuscate($out);
    }

    public function tag_aside($context, $text, $args)
    {
        return "<aside>".$text."</aside>";
    }

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
        if (@$args['equals']) {
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
                $url = $context->url(@$args['verb']);
                if (!$text) {
                    $text = $url['text'];
                }
            }
        }
        //abort if url isn't valid
        //currently this breaks if URLs don't specify http/https
        $url = "$url";
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
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
        //if there is text, make a link
        if ($text) {
            return '<a href="'.$file->url().'" alt="'.$file->name().'">'.$text.'</a>';
        }
        //return metacard for non-image files and mode=card
        if (@$args['mode'] == 'card' || !($file->isImage() || $file->extension() == 'svg')) {
            return $file->metaCard();
        }
        //return img tag otherwise
        return $this->tag_img($context, $text, $args);
    }

    public function tag_figure($context, $text, $args)
    {
        $attr = [];
        $figure = 'no figure specified/found';
        $caption = $text;
        /* suss out the content we're using */
        if (@$args['id']) {
            $figure = $this->tag_file($context, '', $args);
        }
        /* return final markup */
        return "<figure ".implode(' ', $attr).">".
               $figure.
               ($caption?'<figcaption>'.$caption.'</figcaption>':'').
               '</figure>';
    }

    public function tag_img($context, $text, $args)
    {
        $attr = [];
        $preset = @$args['preset']?$args['preset']:'tag-embed';
        $style = '';
        /* figure out the URL to use, which might be from text */
        if (filter_var($text, FILTER_VALIDATE_URL)) {
            /* if text is a URL, use that as the src, this is straight bbcode */
            $url = $text;
        } else {
            /* digraph-integrated img tag */
            $noun = $this->cms->read($context);
            //use noun's image tag handler, if it exists
            if (method_exists($noun, 'tagImg')) {
                return $noun->tagImg($args);
            }
            //default file handler
            $fs = $this->cms->helper('filestore');
            if (!@$args['id']) {
                return false;
            }
            $file = $fs->get($noun, $args['id']);
            if (!$file) {
                return false;
            }
            $file = array_pop($file);
            //return false for non-image files
            if (!$file->isImage()) {
                //svg files are an exception, they can embed
                if ($file->extension() == 'svg') {
                    $url = $file->url();
                } else {
                    return false;
                }
            }
            //use image url otherwise
            $url = $file->imageUrl($preset);
        }
        /* figure out height/width */
        if (@$args['equals'] && preg_match('/^[0-9]+x[0-9]+$/', $args['equals'])) {
            list($args['width'], $args['height']) = explode('x', $args['equals']);
        }
        $height = intval(@$args['height']);
        $width = intval(@$args['width']);
        if ($height > 0) {
            $style .= 'height:'.$height.'px';
        }
        if ($width > 0) {
            $style .= 'width:'.$width.'px';
        }
        /* build alt */
        if ($alt = @$args['alt']) {
            $attr['alt'] = 'alt="'.htmlentities($alt).'"';
        }
        /* build styles */
        //valign
        if ($valign = @$args['valign']) {
            $valign = strtolower($valign);
            if (in_array($valign, ['baseline','text-top','text-bottom','sub','super','top','bottom','middle'])) {
                $style .= 'vertical-align:'.$valign.';';
            }
        }
        //float
        if ($float = @$args['float']) {
            $float = strtolower($float);
            if (in_array($float, ['left','right'])) {
                $style .= 'float:'.$float.';';
            }
        }
        /* build tag */
        $attr['src'] = "src=\"$url\"";
        $attr['class'] = "class=\"digraph-image-embed digraph-image-embed_$preset\"";
        if ($style) {
            $attr['style'] = "style=\"$style\"";
        }
        return "<img ".implode(' ', $attr).">";
    }
}
