<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;

$name = urldecode(Context::url()->actionSuffix());
$time = intval(explode(' ', $name)[0]);
$dayDir = Config::get('paths.storage') . '/exception_log/' . date('Ymd', $time);
$path = "$dayDir/$name.json";

if (!file_exists($path)) throw new HttpError(404);

Context::response()->filename(basename($path));
echo file_get_contents($path);
