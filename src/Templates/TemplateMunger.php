<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Templates;

use Digraph\Mungers\AbstractMunger;

class TemplateMunger extends AbstractMunger
{
    const CACHE_ENABLED = true;

    protected function doMunge(&$package)
    {
        if ($package['response.mime'] != 'text/html') {
            $package->log('templates are only for mime text/html');
            return;
        }
        $t = $package->cms()->helper('templates');
        $t->field('package', $package);
        foreach ($package->get('fields', true) as $key => $value) {
            $t->field($key, $value);
        }
        $package->set(
            'response.content',
            $t->render(
                $package->get('response.template'),
                $package->get('fields')
            )
        );
        $package->set('response.templated', true);
    }

    protected function doConstruct($name)
    {
    }
}
