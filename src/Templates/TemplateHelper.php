<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Templates;

use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;
use Flatrr\SelfReferencingFlatArray;
use Flatrr\FlatArray;

class TemplateHelper extends AbstractHelper
{
    protected $twig;
    protected $fsLoader;
    protected $arrayLoader;
    protected $loader;
    protected $fields = [];
    protected $package = null;

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

    public function css()
    {
        return $this->getThemeConfig('css');
    }

    public function jsHead()
    {
        return $this->getThemeConfig('js-head');
    }

    public function jsFoot()
    {
        return $this->getThemeConfig('js-foot');
    }

    protected function getThemeConfig($name)
    {
        $c = new FlatArray($this->cms->config['theme.'.$name.'._digraph']);
        foreach ($this->theme() as $theme) {
            if ($theme = $this->cms->config['theme.'.$name.'.'.$theme]) {
                $c->merge($theme, null, true);
            }
        }
        $c->merge($this->cms->config['theme._override'], null, true);
        $c = $c->get();
        ksort($c);
        return array_filter($c);
    }

    public function variables()
    {
        return $this->getThemeConfig('variables');
    }

    public function areas()
    {
        if ($theme = $this->cms->config['theme.areas._override']) {
            return $theme;
        }
        foreach ($this->theme() as $theme) {
            if ($theme = $this->cms->config['theme.areas.'.$theme]) {
                return $theme;
            }
        }
        return $this->cms->config['theme.areas._digraph'];
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
            $loaders = [];
            //set up array loader for rendering from strings
            $loaders[] = $this->arrayLoader = new \Twig_Loader_Array();
            //set up basic filesystem loader
            $loaders[] = $this->fsLoader = new \Twig_Loader_Filesystem(
                array_reverse($this->cms->config['templates.paths'])//array of paths to look for templates in
            );
            //set up theme loaders
            if ($themes = $this->theme()) {
                foreach ($themes as $key => $value) {
                    $themes[$key] = '_themes/'.$value;
                }
                $themes[] = '_digraph';
                foreach ($themes as $theme) {
                    $paths = array_reverse($this->cms->config['templates.paths']);
                    foreach ($paths as $key => $value) {
                        $paths[$key] = $value.'/'.$theme;
                    }
                    $paths = array_filter($paths, 'is_dir');
                    if ($paths) {
                        $loaders[] = new \Twig_Loader_Filesystem($paths);
                    }
                }
            }
            //put everything into a chain loader
            $loaders[] = $this->loader = new \Twig_Loader_Chain($loaders);
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

    public function exists($template = 'default.twig', $skipTheme = false)
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
            'cms' => $this->cms,
            'templateName' => $template,
            'url' => $this->cms->config['url']
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
        //check that template exists, then render
        if (!$this->loader->exists($template)) {
            return '<div class="notification notification-error">Error: '.$template.' does not exist</div>';
        }
        $loaded = $env->load($template);
        $package = $this->package;
        $this->package = $fields['package'];
        try {
            return $loaded->render($fields->get());
        } catch (\Exception $e) {
            return '<div class="notification notification-error">Exception rendering '.$template.': '.$e->getMessage().'</div>';
        }
    }
}
