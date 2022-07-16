<?php

use DigraphCMS\Context;

Context::response()->template('chromeless.php');

echo '<div id="' . Context::arg('frame') . '">';
echo "<h1>Rich media</h1>";
echo '</div>';
