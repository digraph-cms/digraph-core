<tocpagebreak links="on"
    even-header-value="off"
    even-footer-value="off"
    odd-header-value="off"
    odd-footer-value="off" />

<?php
$package->noCache();
$package['response.template'] = 'blank-pdf.twig';
$package['response.outputfilter'] = 'pdf';

buildPdfBook($package->noun(), $cms);

function buildPdfBook($noun, &$cms, $level=0)
{
    echo $cms->helper('pdf')->template('article', $noun, ['level'=>$level]);
    // $id = $noun['dso.id'];
    // //load header and footer
    // echo $cms->helper('pdf')->template('hf_noun',$noun);
    // //page break
    // if ($level == 0) {
    //     echo "<sethtmlpageheader name=\"noun_{$id}\" show-this-page=\"1\" value=\"on\" />";
    //     echo "<sethtmlpagefooter name=\"noun_{$id}\" value=\"on\" />";
    //     echo "<sethtmlpageheader name=\"noun_{$id}_even\" show-this-page=\"1\" page=\"even\" value=\"on\" />";
    //     echo "<sethtmlpagefooter name=\"noun_{$id}_even\" page=\"even\" value=\"on\" />";
    // } else {
    //     echo "<pagebreak type=\"next-odd\"";
    //     echo " even-header-value=\"on\"";
    //     echo " even-footer-value=\"on\"";
    //     echo " odd-header-value=\"on\"";
    //     echo " odd-footer-value=\"on\"";
    //     echo " even-header-name=\"noun_{$id}_even\"";
    //     echo " even-footer-name=\"noun_{$id}_even\"";
    //     echo " odd-header-name=\"noun_{$id}\"";
    //     echo " odd-footer-name=\"noun_{$id}\"";
    //     echo " />";
    // }
    // //build content of this noun
    // echo "<div class=\"pdf-article\">";
    // echo "<h1><tocentry content=\"".$noun->title()."\" level=\"$level\">";
    // echo $noun->title()."</h1>";
    // echo $noun->body();
    // echo "</div>";

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
