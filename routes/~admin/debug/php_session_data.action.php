<?php

echo '<h1>PHP session data</h1>';

if (!isset($_COOKIE[session_name()])) {
    echo '<p>No PHP session cookie present.</p>';
    return;
}

@session_start();

echo '<pre>';
print_r($_SESSION);
echo '</pre>';
