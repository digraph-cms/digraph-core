<?php

use DigraphCMS\Context;
use DigraphCMS\Users\Users;

$url = Users::signinUrl(Context::request()->url());
echo $url->html();
