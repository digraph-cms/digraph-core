<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;

class Templates extends AbstractHelper
{
    protected $twig;
    protected $loader;
    protected $fields = [];

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
        $fields = array_replace_recursive($this->fields, $fields);
        //check that template exists, then render
        if ($template = $env->load($template)) {
            return $template->render($fields);
        }
        //return null by default, if template doesn't exist
        return null;
    }
}
