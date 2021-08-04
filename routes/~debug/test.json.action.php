<?php

use DigraphCMS\Context;

Context::response()->mime('application/json');
echo json_encode(['foo', 'bar']);
