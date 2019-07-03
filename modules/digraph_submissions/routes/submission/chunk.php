<?php
if (!$package->noun()->isViewable()) {
    //deny access for those with no access
    $package->error(403);
}
//find chunk
$submission = $package->noun();
$parts = $submission->parts();
$chunks = $parts->chunks();
if (!($chunk = @$chunks[$package['url.args.chunk']])) {
    $package->error(404);
    return;
}
//always make browser-side TTL 0
$package['response.browserttl'] = 0;
$package['fields.page_title'] = '';
//content only template
$package['response.template'] = 'content-only.twig';
echo $chunk->body();
