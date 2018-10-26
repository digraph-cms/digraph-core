<?php
$noun = $package->noun();
$parent = $noun->parent();
if (!$parent) {
    $cms->helper('notifications')->error(
        $cms->helper('strings')->string('version.orphaned')
    );
} else {
    $cms->helper('notifications')->notice(
        $cms->helper('strings')->string(
            'version.notice',
            [
                'parent_name' => $noun->parent()->name(),
                'parent_url' => $noun->parent()->url()
            ]
        )
    );
}
echo $this->helper('filters')->filterContentField($package['noun.digraph.body'], $package['noun.dso.id']);
