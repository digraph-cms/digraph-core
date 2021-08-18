<?php

use DigraphCMS\Config;
use DigraphCMS\Context;

if (!Config::get('errors.display')) {
    echo "<h1>Server error</h1>";
    echo "<div class='error'>Error message display is turned off.</div>";
} else {
    $thrown = Context::thrown();
    echo "<h1>" . get_class($thrown) . "</h1>";
    echo "<div class='error'>";
    echo "<strong>" . htmlentities($thrown->getMessage()) . "</strong>";
    echo '<br>';
    echo trim_file($thrown->getFile()) . ':' . $thrown->getLine();
    echo "</div>";
    echo "<h2>Stack trace</h2>";
    echo "<div class='stack-trace'>";
    foreach ($thrown->getTrace() as $t) {
        echo "<div>";
        if (@$t['file']) {
            echo "<strong>" . htmlentities(trim_file(@$t['file'])) . ":" . @$t['line'] . "</strong><br>";
        }
        echo "<em>" . @$t['class'] . @$t['type'] . @$t['function'] . '()</em>';
        if (@$t['args']) {
            echo "<div class='trace-args'>";
            foreach ($t['args'] as $arg) {
                $arg = htmlentities(print_r($arg, true));
                if (strpos($arg, "\n")) {
                    $id = crc32(serialize([@$t['class'], @$t['type'], @$t['function'], $arg]));
                    echo "<div class=\"collapsible-multiline\" id=\"$id\">";
                    echo "<div id=\"$id-collapsed\">";
                    echo "<a class=\"expand\" href=\"#$id\">+</a>";
                    echo "<a class=\"collapse\" href=\"#$id-collapsed\">-</a>";
                    echo '&nbsp;'.$arg;
                    echo "</div>";
                    echo "</div>";
                } else {
                    echo "<div>$arg</div>";
                }
            }
            echo "</div>";
        }
        echo "</div>";
    }
    echo "</div>";
}

function trim_file($file)
{
    $file = realpath($file);
    $base = realpath(Config::get('paths.base'));
    if (substr($file, 0, strlen($base)) == $base) {
        $file = substr($file, strlen($base));
    }
    return $file;
}
