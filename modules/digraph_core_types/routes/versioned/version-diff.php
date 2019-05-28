<?php
//ensure both exist
$a = $cms->read($package['url.args.a']);
$b = $cms->read($package['url.args.b']);
if (!$a || !$b) {
    $package->error(404, 'A specified version wasn\'t found');
    return;
}

//make sure versions are in the right order
if ($a->effectiveDate() > $b->effectiveDate()) {
    $package->error(404, 'Versions specified in the wrong order');
    return;
}

//make sure parents match
$ap = $a->parent();
$bp = $b->parent();
if ($ap['dso.id'] != $bp['dso.id'] || $ap['dso.id'] != $package['noun.dso.id']) {
    $package->error(404, 'Invalid or mismatched parents');
    return;
}

//get helpers
$s = $cms->helper('strings');
$n = $cms->helper('notifications');

//information for user
$n->notice($s->string(
    'versioned.version-diff.intro',
    [
        'parent_name' => $ap->name(),
        'parent_url' => $ap->url(),
        'a_date' => $s->datetimeHTML($a->effectiveDate()),
        'b_date' => $s->datetimeHTML($b->effectiveDate())
    ]
));

//display output
$granularity = new cogpowered\FineDiff\Granularity\Word;
$diff = new cogpowered\FineDiff\Diff($granularity);
echo "<div class='diff'>";
$text = $diff->render(
    $a->bodyDiffable(),
    $b->bodyDiffable()
);
$text = preg_replace("/([\s]+)<\/del><ins>(.+?)\g{1}<\/ins>/msu", "</del><ins>$2</ins>$1", $text);
$text = str_replace("\t", '<span style="display:inline-block;width:2em;"> </span>', $text);
echo "<div style='white-space:pre-wrap;'>$text</div>";

echo "</div>";
