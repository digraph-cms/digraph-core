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

    public function tag_gallery($context, $text, $args)
    {
        $noun = $this->cms->read($context);
        if (!$noun) {
            return false;
        }
        $args['depth'] = @$args['depth']?intval($args['depth']):-1;
        $args['limit'] = @$args['limit']?intval($args['limit']):0;
        $args['thumb'] = @$args['thumb']?$args['thumb']:'gallery-thumb';
        $args['files'] = $this->gallery_files($noun, $args['depth']);
        usort(
            $args['files'],
            function ($a, $b) {
                $a = $a->time();
                $b = $b->time();
                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? 1 : -1;
            }
        );
        if ($args['limit'] && count($args['files']) > $args['limit']) {
            $args['files'] = array_slice($args['files'], 0, $args['limit']);
        }
        return $this->fromTemplate('_gallery', $context, $text, $args);
    }

    protected function gallery_files(&$noun, $depth)
    {
        $files = [];
        $f = $this->cms->helper('filestore');
        //traverse graph
        $this->cms->helper('graph')
            ->traverse(
                $noun['dso.id'],
                function ($id) use (&$files, $f) {
                    //check noun exists
                    if (!($noun = $this->cms->read($id))) {
                        return false;
                    }
                    //find all files in this noun
                    foreach ($f->listPaths($noun) as $path) {
                        foreach ($f->list($noun, $path) as $file) {
                            if ($file->isImage() && !isset($files[$file->hash()])) {
                                $files[$file->hash()] = $file;
                            }
                        }
                    }
                    return true;
                },
                null,
                $depth
            );
        //return full result
        return $files;
    }
}
