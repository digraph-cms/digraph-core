<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\BBCode;

class BBCodeUnsafeFilter extends AbstractBBCodeFilter
{
    public function tag($tag, $context, $text, $args)
    {
        return $this->tag_template($context, 'tags/unsafe/'.$tag, $args);
    }

    public function tag_block($context, $text, $args)
    {
        return $this->cms->helper('blocks')->block($context);
    }

    public function tag_template($context, $text, $args)
    {
        $t = $this->cms->helper('templates');
        if ($args['template']) {
            $template = $args['template'];
        } else {
            $template = $text;
        }
        if (!$t->exists($template)) {
            return false;
        }
        $fields = $args;
        $fields['noun'] = $this->cms->read($context);
        $fields['text'] = $text;
        $fields['tag'] = $tag;
        return $t->render(
            $template,
            $fields
        );
    }
}
