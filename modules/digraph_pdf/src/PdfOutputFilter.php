<?php
namespace Digraph\Modules\digraph_pdf;

use Mpdf\Mpdf;
use Flatrr\SelfReferencingFlatArray;

class PdfOutputFilter extends \Digraph\OutputFilters\AbstractOutputFilter
{
    public function doFilterPackage(&$package)
    {
        $config = $this->cms->helper('pdf')->config($package->noun());
        $mpdf = $this->cms->helper('pdf')->mpdf($package->noun());
        // $mpdf->WriteHTML('Generated: '.$this->cms->helper('strings')->datetime());
        $mpdf->WriteHTML($package['response.content']);
        // $mpdf->output('', 'S');
        // $package['response.content'] = 'Generated: '.$this->cms->helper('strings')->datetime();
        // $package->makeMediaFile('test.txt');
        $package->makeMediaFile('test.pdf');
        $package->binaryContent($mpdf->output('', 'S'));
        $package->merge($config['package'], null, true);
    }

    public function doPreFilterPackage(&$package)
    {
        $config = $this->cms->helper('pdf')->config($package->noun());
        if (!$config['enabled']) {
            return false;
        }
        $package->merge(
            $this->cms->helper('pdf')->templateFields($package->noun()),
            'fields'
        );
        return true;
    }
}
