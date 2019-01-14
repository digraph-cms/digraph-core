<?php
namespace Digraph\Modules\digraph_pdf;

use Mpdf\Mpdf;
use Flatrr\SelfReferencingFlatArray;

class PdfOutputFilter extends \Digraph\OutputFilters\AbstractOutputFilter
{
    public function doFilterPackage(&$package)
    {
        $mpdf = $this->mpdf($package->noun());
        $mpdf->WriteHTML($package['response.content']);
        $package->makeMediaFile('test.pdf');
        $package->binaryContent($mpdf->output('', 'S'));
    }

    public function doTemplatePackage(&$package)
    {
        $package->merge(
            $this->templateFields($package->noun()),
            'fields'
        );
    }

    protected function mpdf($noun=null)
    {
        //instantiate Mpdf with config from CMS
        $mpdf = new Mpdf(
            $this->config($noun)['mpdf']
        );
        //return object
        return $mpdf;
    }

    protected function css($noun=null)
    {
        return $this->cms->helper('media')->getContent(
            $this->config($noun)['css']
        );
    }

    protected function config($noun=null)
    {
        $config = new SelfReferencingFlatArray($this->cms->config->get('pdf'));
        if ($noun && $noun['pdf']) {
            $config->merge($noun['pdf'], null, true);
        }
        return $config;
    }

    protected function template($name, $noun=null)
    {
        $t = $this->cms->helper('templates');
        //check if config wants to override this template name
        $config = $this->config($noun);
        if ($config['templates.'.$name]) {
            $name = $config['templates.'.$name];
        }
        //return rendered template
        return $t->render(
            'pdf/partials/'.$name.'.twig',
            $this->templateFields($noun)
        );
    }

    protected function templateFields($noun)
    {
        $fields = new SelfReferencingFlatArray();
        $fields->merge($this->cms->config['package.defaults.fields']);
        $fields['noun'] = $noun;
        $fields['pdf'] = $this->config($noun);
        return $fields->get();
    }
}
