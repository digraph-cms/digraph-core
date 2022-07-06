<?php

use DigraphCMS\Context;

Context::response()->enableCache();

echo Context::page()->richContent('body');
