<?php
namespace Digraph\Modules\digraph_pdf;

use Mpdf\Mpdf;
use Flatrr\SelfReferencingFlatArray;

class PdfOutputFilter extends \Digraph\OutputFilters\AbstractOutputFilter
{
    public function doFilterPackage(&$package)
    {
        $mpdf = $this->cms->helper('pdf')->mpdf($package->noun());
        $mpdf->WriteHTML($package['response.content']);
        // $package->makeMediaFile('test.txt');
        $package->makeMediaFile('test.pdf');
        $package->binaryContent($mpdf->output('', 'S'));
    }

    public function doTemplatePackage(&$package)
    {
        $package->merge(
            $this->cms->helper('pdf')->templateFields($package->noun()),
            'fields'
        );
    }
}
