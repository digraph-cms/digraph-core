<?php
echo "<h1>Session dump</h1>";
@session_start();
var_dump($_SESSION);
