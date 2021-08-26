<?php

use DigraphCMS\Session\Session;

echo "<h1>Session dump</h1>";

var_dump(Session::authentication());
var_dump(Session::user());
