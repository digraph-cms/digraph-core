<?php
//load args, make sure two versions are being specified
$vs = array_keys($package['url.args']);
if (count($vs) != 2) {
    $package->error(404, 'Wrong number of versions specified');
    return;
}

//ensure both exist
$b = $cms->read($vs[0]);
$a = $cms->read($vs[1]);
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
$parsedown = new \Parsedown;
$text = $parsedown->text($text);
echo $text;

echo "</div>";
?>
<style>
.diff ins {
    background-color:#8BC34A;
}
.diff del {
    background-color:#f44336;
}
</style>
