<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_core_types;

use Digraph\DSO\Noun;
use HtmlObjectStrings\A;

class Link extends Noun
{
    public function tagLink(array $args = [])
    {
        $link = new A();
        $link->attr('href', $this['url']);
        $link->content = $this->name();
        return $link;
    }

    public function tagEmbed(array $args = [])
    {
        return $this->tagLink($args);
    }
}
