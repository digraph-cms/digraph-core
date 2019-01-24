<tocpagebreak links="on"
    even-header-value="off"
    even-footer-value="off"
    odd-header-value="off"
    odd-footer-value="off" />

<?php
$package['response.template'] = 'blank-pdf.twig';
$package['response.outputfilter'] = 'pdf';
$package['pdf.filename.name'] = '${fields.page_name} pdfbook';

buildPdfBook($package['noun.dso.id'], $package->noun(), $cms);

function buildPdfBook($rootID, $noun, &$cms, $extraTOCEntries=[], $level=0)
{
    if ($cms->helper('pdf')->config($noun)['include_in_books']) {
        echo $cms->helper('pdf')->template(
            'article',
            $noun,
            [
                'level'=>$level,
                'firstArticle' => ($rootID == $noun['dso.id']),
                'extraTOCEntries'=>$extraTOCEntries
            ]
        );
        $extraTOCEntries = [];
    } else {
        $extraTOCEntries[] = [
            'noun' => $noun,
            'level' => $level
        ];
    }
    //recurse into children
    foreach ($noun->children() as $child) {
        buildPdfBook($rootID, $child, $cms, $extraTOCEntries, $level+1);
        $extraTOCEntries = [];
    }
}
?>

<!-- turn off headers so they don't show up in toc -->
<!-- this also turns off the footer on the last page, but I don't know how to fix that -->
<sethtmlpageheader value="off" />
<sethtmlpageheader page="even" value="off" />
<sethtmlpagefooter value="off" />
<sethtmlpagefooter page="even" value="off" />
