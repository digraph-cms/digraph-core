<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\BBCode;

use Digraph\Filters\VideoServices;

class BBCodeAdvancedFilter extends AbstractBBCodeFilter
{
    const TEMPLATEPREFIX = '_bbcode/advanced/';

    public function tag_video($context, $text, $args)
    {
        $url = @$args['url'] ?? $text;
        $service = @$args['service'];
        $id = @$args['id'];
        // parse URL if necessary
        if (!$service || !$id) {
            $parsed = VideoServices::parse($url);
            if (!$parsed) {
                return '{video url couldn\'t be parsed}';
            }
            list($service, $id) = $parsed;
        }
        // attempt to build embed
        if (!$embed = VideoServices::embed($service, $id)) {
            return '{video service not found}';
        }
        // return embedded video
        return '<div class="video-embed" data-service="' . $service . '" data-id="' . $id . '"><div class="video-embed-wrapper">' . $embed . '</div></div>';
    }

    public function tag_toc($context, $text, $args)
    {
        $noun = $this->cms->read($context);
        if (!$noun) {
            return false;
        }
        $depth = @$args['depth'] ? intval($args['depth']) : -1;
        return $this->toc_helper($noun, $depth) . PHP_EOL;
    }

    protected function toc_helper($noun, $depth = -1, $seen = [])
    {
        $depth--;
        if ($depth == -1) {
            return '';
        }
        if (in_array($noun['dso.id'], $seen) || !($children = $noun->children())) {
            return '';
        }
        $seen[] = $noun['dso.id'];
        $out = '';
        foreach ($children as $c) {
            if (in_array($c['dso.id'], $seen)) {
                continue;
            }
            $out .= '<li>' . $c->link();
            $out .= $this->toc_helper($c, $depth, $seen);
            $out .= '</li>';
        }
        if ($out) {
            $out = '<ul class="digraph-toc">' . $out . '</ul>';
        }
        return $out;
    }

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
