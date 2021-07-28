<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */

namespace Digraph\Templates;

use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;
use Flatrr\FlatArray;
use Flatrr\SelfReferencingFlatArray;

class TemplateHelper extends AbstractHelper
{
    protected $twig;
    protected $fsLoader;
    protected $arrayLoader;
    protected $loader;
    protected $fields = [];
    protected $package = null;
    protected $css = [];
    protected $headJS = [];
    protected $footJS = [];
    protected $prefetch = [];

    /**
     * Get all the paths from CMS config, with 'site' swapped to the end, and
     * the entire thing reversed. This way 'site' is highest priority, and
     * 'core' is lowest priority.
     *
     * @return array
     */
    protected function paths()
    {
        $paths = $this->cms->config['templates.paths'];
        if ($site = @$paths['site']) {
            unset($paths['site']);
            $paths['site'] = $site;
        }
        return array_reverse($paths);
    }

    public function addPrefetch($url)
    {
        $this->prefetch[] = $url;
        $this->prefetch = array_unique($this->prefetch);
    }

    public function addCSS($url)
    {
        $this->css[] = $url;
        $this->css = array_unique($this->css);
    }

    public function addHeadJS($url)
    {
        $this->headJS[] = $url;
        $this->headJS = array_unique($this->headJS);
    }

    public function addFootJS($url)
    {
        $this->footJS[] = $url;
        $this->footJS = array_unique($this->footJS);
    }

    public function assetUrl($url)
    {
        // if it's a URL this is easy
        if ($url instanceof Url) {
            $asset = $this->cms->helper('media')->get(
                preg_replace('/\/$/', '', $url->pathString())
            );
            return $asset ? $asset['url'] : $url;
        }
        // if it's prefixed with the base URL we can also search
        $base = $this->cms->config['url.base'];
        if (substr($url, 0, strlen($base)) == $base) {
            $asset = $this->cms->helper('media')->get(
                substr($url, strlen($base))
            );
            return $asset ? $asset['url'] : $url;
        }
        // otherwise we just return what we got
        return $url;
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

    public function prefetch()
    {
        $urls = $this->getThemeConfig('prefetch');
        //add custom-added css
        $urls = $urls + $this->prefetch;
        //return
        return array_map(
            [$this, 'assetUrl'],
            array_unique($urls)
        );
    }

    public function css()
    {
        $css = $this->getThemeConfig('css');
        //add noun/verb-driven route media
        $nouns = [];
        if ($noun = $this->cms->package()->noun()) {
            $nouns = $noun::ROUTING_NOUNS;
            $nouns[] = $noun['dso.type'];
            $nouns = array_unique($nouns);
        }
        $linkedCSS = '';
        $verbs = [$this->cms->package()['url.verb']];
        array_unshift($nouns, '_');
        array_unshift($verbs, '_');
        foreach ($nouns as $noun) {
            foreach ($verbs as $verb) {
                if ($link = $this->cms->helper('media')->getContent('_routemedia/' . $noun . '/' . $verb . '/linked.css')) {
                    $linkedCSS .= "/* $noun $verb */" . PHP_EOL;
                    $linkedCSS .= $link . PHP_EOL;
                }
            }
        }
        if ($linkedCSS) {
            $linkedCSS = $this->cms->helper('media')->create(
                'linked.css',
                $linkedCSS
            );
            $css[] = $linkedCSS['url'];
        }
        //add custom-added css
        $css = $css + $this->css;
        //return
        return array_map(
            [$this, 'assetUrl'],
            array_unique($css)
        );
    }

    public function jsHead()
    {
        $out = $this->getThemeConfig('js-head');
        $out = $out + $this->headJS;
        return array_map(
            [$this, 'assetUrl'],
            array_unique($out)
        );
    }

    public function jsFoot()
    {
        $out = $this->getThemeConfig('js-foot');
        $out = $out + $this->footJS;
        return array_map(
            [$this, 'assetUrl'],
            array_unique($out)
        );
    }

    protected function getThemeConfig($name)
    {
        $c = new FlatArray($this->cms->config['theme.' . $name . '._digraph']);
        foreach ($this->theme() as $theme) {
            if ($theme = $this->cms->config['theme.' . $name . '.' . $theme]) {
                $c->merge($theme, null, true);
            }
        }
        $c->merge($this->cms->config['theme.' . $name . '._override'], null, true);
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
            if ($theme = $this->cms->config['theme.areas.' . $theme]) {
                return $theme;
            }
        }
        return $this->cms->config['theme.areas._digraph'];
    }

    public function link($url, $text = null)
    {
        if ($url instanceof Url) {
            $link = $this->urlLinkObject($url, $text);
        } elseif ($url && $url = $this->cms->helper('urls')->parse($url)) {
            $link = $this->urlLinkObject($url, $text);
        } else {
            return '[' . $url . ' not found]';
        }
        //short circuit for error pages, so they don't have any active-* classes
        //making the output of error pages consistent helps make munger caching
        //as efficient as possible
        if ($this->package['response.status'] != 200) {
            return $link;
        }
        //add active status based on breadcrumb
        $breadcrumb = @$this->cms->helper('navigation')->breadcrumb($this->package->url());
        if ($url->pathString(true) == end($breadcrumb)->pathString(true)) {
            $link->addClass('active-page');
            $link->addClass('selected');
        } elseif ($url->pathString() != '') {
            foreach ($breadcrumb as $bc) {
                if ($url->pathString(true) == $bc->pathString(true)) {
                    $link->addClass('active-path');
                    $link->addClass('selected');
                }
            }
        }
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

    public function env()
    {
        if (!$this->loader) {
            $loaders = [];
            //set up array loader for rendering from strings
            $loaders[] = $this->arrayLoader = new \Twig_Loader_Array();
            //set up basic filesystem loader
            $loaders[] = $this->fsLoader = new \Twig_Loader_Filesystem(
                $this->paths() //array of paths to look for templates in
            );
            //set up theme loaders
            if ($themes = $this->theme()) {
                foreach ($themes as $key => $value) {
                    $themes[$key] = '_themes/' . $value;
                }
                $themes[] = '_digraph';
                foreach ($themes as $theme) {
                    $paths = array_reverse($this->cms->config['templates.paths']);
                    foreach ($paths as $key => $value) {
                        $paths[$key] = $value . '/' . $theme;
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

    public function renderString(string $template, $fields = array())
    {
        //add to arrayLoader
        $this->env();
        $id = 'digraph_arrayloader_' . md5($template);
        $this->arrayLoader->setTemplate("{$id}", $template);
        //pass off to normal rendering
        return $this->render($id, $fields);
    }

    public function exists($template = 'default.twig', $skipTheme = false)
    {
        $this->env();
        return $this->loader->exists($template);
    }

    public function render($template = 'default.twig', $fields = array())
    {
        $this->cms->log('rendering template: ' . $template);
        //set template name and get environment
        $env = $this->env();
        //merge fields
        $fields = new SelfReferencingFlatArray($fields);
        $fields->merge($this->fields);
        $fields->merge([
            'helper' => $this,
            'config' => $this->cms->config,
            'cms' => $this->cms,
            'templateName' => $template,
            'url' => $this->cms->config['url'],
        ]);
        if ($fields['package']) {
            $fields->merge(
                $fields['package']->get('fields'),
                null,
                true
            );
            $fields->merge(
                ['noun' => $fields['package']->noun()],
                null,
                false
            );
        }
        //check that template exists, then render
        if (!$this->loader->exists($template)) {
            return '<div class="notification notification-error">Error: ' . $template . ' does not exist</div>';
        }
        $loaded = $env->load($template);
        $package = $this->package;
        $this->package = $fields['package'];
        try {
            $this->cms->log('rendered template: ' . $template);
            return $loaded->render($fields->get());
        } catch (\Exception $e) {
            return '<div class="notification notification-error">Exception rendering ' . $template . ': ' . $e->getMessage() . '</div>';
        }
    }
}
