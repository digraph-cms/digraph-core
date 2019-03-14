<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\BBCode;

class BBCodeAdvancedFilter extends AbstractBBCodeFilter
{
    const TEMPLATEPREFIX = '_bbcode/advanced/';

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
        $depth = @$args['depth']?$args['depth']:-1;
        $args['thumb'] = @$args['thumb']?$args['thumb']:'gallery-thumb';
        $args['files'] = $this->gallery_files($noun, $depth);
        return $this->fromTemplate('_gallery', $context, $text, $args);
    }

    protected function gallery_files(&$noun, $depth)
    {
        //base case when depth is 0
        if ($depth == 0) {
            return [];
        }
        //recurse
        $files = [];
        foreach ($noun->children() as $child) {
            foreach ($this->gallery_files($child, $depth-1) as $file) {
                if (!isset($files[$file->hash()])) {
                    $files[$file->hash()] = $file;
                }
            }
        }
        //find all files in this noun
        $f = $this->cms->helper('filestore');
        foreach ($f->listPaths($noun) as $path) {
            foreach ($f->list($noun, $path) as $file) {
                if ($file->isImage() && !isset($files[$file->hash()])) {
                    $files[$file->hash()] = $file;
                }
            }
        }
        //return full result
        return $files;
    }
}
