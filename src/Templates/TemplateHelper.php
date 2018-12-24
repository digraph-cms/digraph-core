<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Templates;

use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;
use Flatrr\SelfReferencingFlatArray;

class TemplateHelper extends AbstractHelper
{
    protected $twig;
    protected $fsLoader;
    protected $arrayLoader;
    protected $loader;
    protected $fields = [];
    protected $package = null;

    public function themeTemplate($file)
    {
        $files = [];
        foreach (array_reverse($this->theme()) as $theme) {
            $files[] = "_themes/$theme/$file";
        }
        $files[] = "_digraph/$file";
        foreach ($files as $file) {
            if ($this->exists($file)) {
                return $file;
            }
        }
        return "_digraph/_notfound.twig";
    }

    public function theme()
    {
        $theme = $this->cms->config['templates.theme'];
        if (!$theme) {
            return [];
        }
        if (!is_array($theme)) {
            return [$theme];
        }
        return $theme;
    }

    public function cssUrls()
    {
        $urls = $this->cms->config['templates.css'];
        return $urls;
    }

    public function headJSUrls()
    {
        $urls = $this->cms->config['templates.js.head'];
        return $urls;
    }

    public function footJSUrls()
    {
        $urls = $this->cms->config['templates.js.foot'];
        return $urls;
    }

    public function link($url, $text=null)
    {
        if ($url instanceof Url) {
            $link = $this->urlLinkObject($url, $text);
        } elseif ($url && $url = $this->cms->helper('urls')->parse($url)) {
            $link = $this->urlLinkObject($url, $text);
        } else {
            return '['.$url.' not found]';
        }
        //short circuit for error pages, so they don't have any active-* classes
        //making the output of error pages consistent helps make munger caching
        //as efficient as possible
        if ($this->package['response.status'] != 200) {
            return $link;
        }
        //add active status based on breadcrumb
        $breadcrumb = @$this->cms->helper('navigation')->breadcrumb($this->package->url());
        if (@isset($breadcrumb["$url"])) {
            if ($url->pathString() == '' && count($breadcrumb) > 1) {
                return $link;
            }
            if ($breadcrumb["$url"] == end($breadcrumb)) {
                $link->addClass('active-page');
            } else {
                $link->addClass('active-path');
            }
            $link->addClass('selected');
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
            $this->fsLoader = new \Twig_Loader_Filesystem(
                array_reverse($this->cms->config['templates.paths'])//array of paths to look for templates in
            );
            $this->arrayLoader = new \Twig_Loader_Array();
            $this->loader = new \Twig_Loader_Chain([
                $this->fsLoader,
                $this->arrayLoader
            ]);
            //set up twig environment with loader and config from cms config
            $this->twig = new \Twig_Environment(
                $this->loader,
                $this->cms->config['templates.twigconfig']
            );
        }
        return $this->twig;
    }

    public function renderString(string $template, $fields=array())
    {
        //add to arrayLoader
        $this->env();
        $id = 'digraph_arrayloader_'.md5($template);
        $this->arrayLoader->setTemplate("{$id}", $template);
        //pass off to normal rendering
        return $this->render($id, $fields);
    }

    public function exists($template = 'default.twig')
    {
        $this->env();
        return $this->loader->exists($template);
    }

    public function render($template = 'default.twig', $fields=array())
    {
        //set template name and get environment
        $env = $this->env();
        //merge fields
        $fields = new SelfReferencingFlatArray($fields);
        $fields->merge($this->fields);
        $fields->merge([
            'helper' => &$this,
            'config' => $this->cms->config,
            'cms' => $this->cms
        ]);
        if ($fields['package']) {
            $fields->merge(
                $fields['package']->get('fields'),
                null,
                true
            );
            $fields->merge(
                ['noun'=>$fields['package']->noun()],
                null,
                false
            );
        }
        //first try to load template from themes, if it exists
        $templates = [];
        foreach (array_reverse($this->theme()) as $theme) {
            $templates[] = "_themes/$theme/$template";
        }
        $templates[] = $template;
        //check that template exists, then render
        foreach ($templates as $template) {
            if ($this->exists($template)) {
                $template = $env->load($template);
                $package = $this->package;
                $this->package = $fields['package'];
                return $template->render($fields->get());
            }
        }
        //return null by default, if template doesn't exist
        return null;
    }
}
