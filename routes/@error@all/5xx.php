<p>An unrecoverable error occurred while generating the page. If this problem persists, contact the webmaster.</p>

<?php
$package['fields.page_name'] = $package['fields.page_title'] = 'Server error';

$package->saveLog('server error', Digraph\Logging\LogHelper::ERROR);
