<?php
$package->noCache();

echo "<pre>";
echo htmlentities($cms->config->yaml(true));
echo "</pre>";
