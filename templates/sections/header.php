<?php

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

$url = new URL('/');
echo "<header id=\"header\">";
echo "<h1><a href='$url'>" . Context::fields()['site.name'] . "</a></h1>";
echo "</header>";
