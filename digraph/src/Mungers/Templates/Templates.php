<?php
/* Digraph CMS | https://github.com/digraphcms/digraph | MIT License */
namespace Digraph\CMS\Mungers\Templates;

use Digraph\Mungers\AbstractMunger;

class Templates extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        if ($package['response.mime'] != 'text/html') {
            $package->log('templates are only for mime text/html');
            return;
        }
        $t = $package->cms()->helper('templates');
        $t->field('package', $package);
        $package->set(
            'response.content',
            $t->render(
                $package->get('response.template'),
                []
            )
        );
    }

    protected function doConstruct($name)
    {
    }
}
