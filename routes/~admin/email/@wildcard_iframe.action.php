<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Emails;
use DigraphCMS\HTTP\HttpError;

$email = Emails::get(Context::url()->actionSuffix());
if (!$email) throw new HttpError(404);

Context::response()->template('null.php');

echo Emails::prepareBody_html($email);
