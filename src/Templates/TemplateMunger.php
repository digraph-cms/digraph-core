<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Templates;

use Digraph\Mungers\AbstractMunger;

class TemplateMunger extends AbstractMunger
{
    const CACHE_ENABLED = true;

    protected function doMunge(&$package)
    {
        // only run templates for text/html
        if ($package['response.mime'] != 'text/html') {
            $package->log('templates are only for mime text/html');
            return;
        }
        // load template helper
        $t = $package->cms()->helper('templates');
        $template = $package->template();
        // build fields and render
        $t->field('package', $package);
        foreach ($package->get('fields', true) as $key => $value) {
            $t->field($key, $value);
        }
        $package->set(
            'response.content',
            $t->render(
                $template,
                $package->get('fields')
            )
        );
        $package->set('response.templated', $template);
    }

    protected function doConstruct($name)
    {
    }
}
