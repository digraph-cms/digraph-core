<?php
$package->noCache();
$package['response.outputfilter'] = 'pdf';
echo $package->noun()->body();
