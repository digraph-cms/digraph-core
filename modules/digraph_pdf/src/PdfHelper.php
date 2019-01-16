<?php
namespace Digraph\Modules\digraph_pdf;

use Mpdf\Mpdf;
use Flatrr\SelfReferencingFlatArray;

class PdfHelper extends \Digraph\Helpers\AbstractHelper
{
    public function mpdf($noun=null)
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

    public function config($noun=null)
    {
        $config = new SelfReferencingFlatArray($this->cms->config->get('pdf'));
        if ($noun && $noun['pdf']) {
            $config->merge($noun['pdf'], null, true);
        }
        $config['package.response.ttl'] = intval($config['package.response.ttl']);
        $config['mpdf.mirrorMargins'] = $config['mpdf.mirrorMargins']?1:0;
        return $config;
    }

    public function template($name, $noun=null, $fields=[])
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
            $this->templateFields($noun, $fields)
        );
    }

    public function templateFields($noun, $fields=[])
    {
        $fields = new SelfReferencingFlatArray($fields);
        $fields->merge($this->cms->config['package.defaults.fields']);
        $fields['noun'] = $noun;
        $fields['pdf'] = $this->config($noun);
        return $fields->get();
    }
}
