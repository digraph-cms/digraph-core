<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_core_types;

use Digraph\DSO\Noun;
use HtmlObjectStrings\A;

class Link extends Noun
{
    const PUBLISH_CONTROL = false;
    const SLUG_ENABLED = true;

    public function formMap(string $actions) : array
    {
        $s = $this->factory->cms()->helper('strings');
        $map = parent::formMap($actions);
        $map['001_digraph_title'] = false;
        $map['500_digraph_body'] = false;
        $map['400_link_url'] = [
            'field' => 'url',
            'label' => $s->string('forms.link.url_label'),
            'class' => 'Formward\\Fields\\Url',
            'required' => true
        ];
        $map['401_showpage'] = [
            'field' => 'link.showpage',
            'label' => $s->string('forms.link.showpage'),
            'class' => 'Formward\Fields\Checkbox'
        ];
        return $map;
    }

    public function tag_link($text=null, array $args = [])
    {
        $link = new A();
        $link->attr('href', $this['url']);
        $link->addClass('digraph-link');
        $link->attr('data-digraph-link', $this->url());
        $link->content = $this->name();
        if ($text) {
            $link->content = $text;
        }
        return $link;
    }
}
