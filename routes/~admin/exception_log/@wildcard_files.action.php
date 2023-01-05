<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;

$name = urldecode(Context::url()->actionSuffix());
$time = intval(explode(' ', $name)[0]);
$dayDir = Config::get('paths.storage') . '/exception_log/' . date('Ymd', $time);
$path = "$dayDir/$name.json";
$files = "$dayDir/$name.zip";

if (!file_exists($path) || !file_exists($files)) throw new HttpError(404);

Context::response()->filename(basename($files));
echo file_get_contents($files);
