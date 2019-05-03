<?php
foreach ($this->helper('routing')->allHookFiles($package['noun.dso.type'], 'display_first.php') as $file) {
    include $file['file'];
}

foreach ($this->helper('routing')->allHookFiles($package['noun.dso.type'], 'display_before.php') as $file) {
    include $file['file'];
}

echo $package->noun()->body();

foreach ($this->helper('routing')->allHookFiles($package['noun.dso.type'], 'display_after.php') as $file) {
    include $file['file'];
}

foreach ($this->helper('routing')->allHookFiles($package['noun.dso.type'], 'display_last.php') as $file) {
    include $file['file'];
}
