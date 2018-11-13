<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_core_types;

use Digraph\DSO\Noun;
use HtmlObjectStrings\A;

class Link extends Noun
{
    public function formMap(string $actions) : array
    {
        $s = $this->factory->cms()->helper('strings');
        return [
            '001_digraph_title' => false,
            '400_link_url' => [
                'field' => 'url',
                'label' => $s->string('forms.links.url_label'),
                'class' => 'Formward\\Fields\\Url',
                'required' => true
            ]
        ];
    }

    public function tagLink(array $args = [])
    {
        $link = new A();
        $link->attr('href', $this['url']);
        $link->addClass('digraph-link');
        $link->attr('data-digraph-link', $this->url());
        $link->content = $this->name();
        return $link;
    }

    public function tagEmbed(array $args = [])
    {
        return $this->tagLink($args);
    }
}
