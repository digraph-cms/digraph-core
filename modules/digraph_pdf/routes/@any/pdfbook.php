<tocpagebreak links="on"
    even-header-value="off"
    even-footer-value="off"
    odd-header-value="off"
    odd-footer-value="off" />

<?php
$package['response.template'] = 'blank-pdf.twig';
$package['response.outputfilter'] = 'pdf';

buildPdfBook($package->noun(), $cms);

function buildPdfBook($noun, &$cms, $level=0)
{
    echo $cms->helper('pdf')->template('article', $noun, ['level'=>$level]);
    //recurse into children
    foreach ($noun->children() as $child) {
        buildPdfBook($child, $cms, $level+1);
    }
}
?>

<!-- turn off headers so they don't show up in toc -->
<!-- this also turns off the footer on the last page, but I don't know how to fix that -->
<sethtmlpageheader value="off" />
<sethtmlpageheader page="even" value="off" />
<sethtmlpagefooter value="off" />
<sethtmlpagefooter page="even" value="off" />
