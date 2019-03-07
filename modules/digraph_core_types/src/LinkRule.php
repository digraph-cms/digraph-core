<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_core_types;

use Digraph\DSO\Noun;
use HtmlObjectStrings\A;

class LinkRule extends Noun
{
    const PUBLISH_CONTROL = false;
    const SLUG_ENABLED = true;

    public function formMap(string $actions) : array
    {
        $s = $this->factory->cms()->helper('strings');
        $map = parent::formMap($actions);
        $map['001_digraph_title'] = false;
        $map['500_digraph_body'] = false;
        $map['400_linkrule_rules'] = [
            'field' => 'rules',
            'label' => $s->string('forms.linkrule.rules_label'),
            'class' => 'Formward\\Fields\\Textarea',
            'required' => true
        ];
        $map['401_showpage'] = [
            'field' => 'link.showpage',
            'label' => $s->string('forms.link.showpage'),
            'class' => 'Formward\Fields\Checkbox'
        ];
        return $map;
    }

    public function linkUrl($text, $args=[])
    {
        $rules = preg_split('/[\r\n]+/', $this['rules']);
        $args['text'] = $text;//simplify input
        foreach ($rules as $rule) {
            //skip comments starting with #
            if ($rule[0] == '#') {
                continue;
            }
            //otherwise pass to rule processing
            if ($pattern = $this->parseRule($rule, $args)) {
                return $this->expandPattern($pattern, $args);
            }
        }
        //nothing matched!
        return null;
    }

    protected function expandPattern($pattern, $args)
    {
        //replace variables
        foreach ($args as $key => $value) {
            $pattern = str_replace('$'.$key, $value, $pattern);
        }
        return $pattern;
    }

    protected function parseRule($rule, $args)
    {
        list($condition, $pattern) = preg_split('/: */', $rule, 2);
        //default always applies
        if ($condition == 'default') {
            return $pattern;
        }
        //conditions based on an arg
        if ($condition[0] == '$') {
            $condition = substr($condition, 1);
            list($arg, $condition) = preg_split('/\s+/', $condition, 2);
            //"set" to check if arg is set
            if ($condition == 'set' && isset($args[$arg])) {
                return $pattern;
            }
            //default to returning null
            return null;
        }
        //default to null
        return null;
    }

    public function tag_link($text=null, array $args = [])
    {
        $link = new A();
        $link->attr('href', $this->linkUrl($text, $args));
        $link->addClass('digraph-link-intercept');
        $link->attr('data-digraph-link', $this->url(null, $args));
        $link->content = $this->name();
        if ($text) {
            $link->content = $text;
        }
        return $link;
    }
}
