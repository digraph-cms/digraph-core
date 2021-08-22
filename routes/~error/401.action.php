<h1>Access denied</h1>
<p>You were denied access to this page 

<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Users\Users;

if ($message = Context::data('error_message')) {
    echo "with the message \"$message\"";
}
echo "</p>";

$user = Users::current();
$signinUrl = Users::signinUrl(Context::request()->originalUrl());
if ($user) {
    $signoutUrl = Users::signoutUrl($signinUrl);
    echo "<p>You are currently signed in as $user. If this is not you, please <a href='$signoutUrl'>sign out</a> and try again.<p>";
}else {
    echo "<p>You are not signed in, you can try <a href='$signinUrl'>signing in</a> if you have an account.</p>";
}

Router::include('trace.php');