<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Templates;

use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;
use Flatrr\SelfReferencingFlatArray;

class TemplateHelper extends AbstractHelper
{
    protected $twig;
    protected $loader;
    protected $fields = [];
    protected $package = null;

    public function link($url, $text=null)
    {
        if ($url instanceof Url) {
            $link = $this->urlLinkObject($url, $text);
        } elseif ($url && $url = $this->cms->helper('urls')->parse($url)) {
            $link = $this->urlLinkObject($url, $text);
        } else {
            return '['.$url.' not found]';
        }
        //add active status based on breadcrumb
        $breadcrumb = @$this->cms->helper('navigation')->breadcrumb($this->package->url());
        if (@isset($breadcrumb["$url"])) {
            if ($url->pathString() == '' && count($breadcrumb) > 1) {
                return $link;
            }
            $link->addClass('active');
            $link->attr('aria-selected', 'true');
        }
        //return link
        return $link;
    }

    public function urlLinkObject($url, $text)
    {
        $a = $url->html($text);
        return $a;
    }

    public function field(string $name, $value)
    {
        $this->fields[$name] = $value;
    }

    public function &env()
    {
        if (!$this->loader) {
            //set up loader
            $this->loader = new \Twig_Loader_Filesystem(
                array_reverse($this->cms->config['templates.paths'])//array of paths to look for templates in
            );
            //set up twig environment with loader and config from cms config
            $this->twig = new \Twig_Environment(
                $this->loader,
                $this->cms->config['templates.twigconfig']
            );
        }
        return $this->twig;
    }

    public function render($template = 'default', $fields=array())
    {
        //set template name and get environment
        $template .= '.twig';
        $env = $this->env();
        //merge fields
        $fields = new SelfReferencingFlatArray($fields);
        $fields->merge($this->fields);
        $fields->merge([
            'helper' => &$this
        ]);
        $fields->merge(
            $fields['package']->get('fields'),
            null,
            true
        );
        //check that template exists, then render
        if ($template = $env->load($template)) {
            $package = $this->package;
            $this->package = $fields['package'];
            return $template->render($fields->get());
            $this->package = $package;
        }
        //return null by default, if template doesn't exist
        return null;
    }
}
