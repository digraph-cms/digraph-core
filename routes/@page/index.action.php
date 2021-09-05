<?php

use DigraphCMS\Context;
use DigraphCMS\Editor\Blocks;

$blocks = new Blocks(Context::page()['content']);
echo $blocks->render();
