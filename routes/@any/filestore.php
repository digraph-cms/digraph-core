<?php
if (!$package->noun()::FILESTORE) {
    $package->error(404, 'filestore not enabled for this type');
    exit();
}
