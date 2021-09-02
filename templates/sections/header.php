<?php

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

$url = new URL('/');
echo "<h1><a href='$url'>". Context::fields()['site.name'] ."</a></h1>";