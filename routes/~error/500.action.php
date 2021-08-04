<?php

use DigraphCMS\Config;
use DigraphCMS\Context;

if (!Config::get('errors.display')) {
    echo "<h1>Server error</h1>";
    echo "<div class='notification error'>Error message display is turned off.</div>";
} else {
    $thrown = Context::thrown();
    echo "<h1><code>" . get_class($thrown) . "</code></h1>";
    echo "<div class='notification error'>";
    echo "<strong style='color:#900;'><code>" . $thrown->getMessage() . "</code></strong>";
    echo '<br>';
    echo '<code style="color:#066;">' . trim_file($thrown->getFile()) . ':' . $thrown->getLine() . '</code>';
    echo "</div>";
    echo "<h2><code>Stack trace</code></h2>";
    echo "<ul>";
    foreach ($thrown->getTrace() as $t) {
        echo "<li>";
        echo "<strong style='color:#039;'><code>" . trim_file($t['file']) . ":$t[line]</code></strong><br>";
        echo '<code style="color:#066;">' . @$t['class'] . @$t['type'] . @$t['function'] . '()</code>';
        if (@$t['args']) {
            echo "<ol>";
            foreach ($t['args'] as $arg) {
                echo "<li><code>" . serialize($arg) . "</code></li>";
            }
            echo "</ol>";
        }
        echo "</li>";
    }
    echo "<ul>";
    echo "</ul>";
}

function trim_file($file)
{
    $base = Config::get('paths.base');
    if (substr($file, 0, strlen($base)) == $base) {
        $file = substr($file, strlen($base));
    }
    return $file;
}
