<?php
if (!($version = $package->noun()->currentVersion())) {
    $cms->helper('notifications')->warning(
        $cms->helper('strings')->string('versioned.no_versions')
    );
    return;
}

$package['fields.page_title'] = $version->title();
echo $this->helper('filters')->filterContentField($version['digraph.body'], $package['noun.dso.id']);

if (!$version->isPublished()) {
    $cms->helper('notifications')->warning(
        $cms->helper('strings')->string(
            'notifications.unpublished',
            ['name'=>$version->name()]
        )
    );
}
