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

    public function link($slug, $text=null)
    {
        if ($slug instanceof Url) {
            return $this->urlLinkObject($slug, $text);
        } elseif ($url = $this->cms->helper('urls')->parse($slug)) {
            return $this->urlLinkObject($url, $text);
        }
        return '['.$slug.' not found]';
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
            return $template->render($fields->get());
        }
        //return null by default, if template doesn't exist
        return null;
    }
}
