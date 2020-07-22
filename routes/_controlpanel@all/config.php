<?php
$package->cache_noStore();

echo "<pre>";
echo htmlentities($cms->config->yaml(true));
echo "</pre>";
