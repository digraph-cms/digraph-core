<?php

use DigraphCMS\Context;
use DigraphCMS\Messaging\Messages;

Context::response()->private(true);

$message = Messages::get(Context::url()->action());
