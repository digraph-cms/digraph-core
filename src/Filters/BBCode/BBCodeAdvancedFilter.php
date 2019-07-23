<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\BBCode;

class BBCodeAdvancedFilter extends AbstractBBCodeFilter
{
    const TEMPLATEPREFIX = '_bbcode/advanced/';

    public function tag_toc($context, $text, $args)
    {
        $noun = $this->cms->read($context);
        if (!$noun) {
            return false;
        }
        $depth = @$args['depth']?intval($args['depth']):-1;
        return $this->toc_helper($noun, $depth).PHP_EOL;
    }

    protected function toc_helper($noun, $depth=-1, $seen=[])
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
            $out .= '<li>'.$c->link();
            $out .= $this->toc_helper($c, $depth, $seen);
            $out .= '</li>';
        }
        if ($out) {
            $out = '<ul class="digraph-toc">'.$out.'</ul>';
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
