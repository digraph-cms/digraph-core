<?php

use DigraphCMS\Content\Route;
?>
<p>This is the index action for any Page, so that there's a fallback basic content display action available.</p>
<?php
var_dump(Route::page());
