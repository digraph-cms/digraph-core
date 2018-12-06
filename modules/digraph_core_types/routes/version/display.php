<?php
$noun = $package->noun();
$parent = $noun->parent();
if (!$parent) {
    $cms->helper('notifications')->error(
        $cms->helper('strings')->string('version.orphaned')
    );
} else {
    $s = $cms->helper('strings');
    $cms->helper('notifications')->notice(
        $s->string(
            'version.notice',
            [
                'parent_name' => $noun->parent()->name(),
                'parent_url' => $noun->parent()->url(),
                'version_date' => $s->datetimeHTML($noun->effectiveDate())
            ]
        )
    );
}
echo $this->helper('filters')->filterContentField($package['noun.digraph.body'], $package['noun.dso.id']);
