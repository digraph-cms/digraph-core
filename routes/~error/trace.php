<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Users\Permissions;

if (Config::get('errors.display_trace') || (Config::get('errors.display_trace_to_admins') && Permissions::inGroup('admins'))) {
    if (!($thrown = Context::thrown())) {
        return;
    }
    $trim = function ($file) {
        $file = realpath($file);
        $base = realpath(Config::get('paths.base'));
        if (substr($file, 0, strlen($base)) == $base) {
            $file = substr($file, strlen($base));
        }
        return $file;
    };
    echo "<section class='stack-trace'>";
    echo "<h1>Stack trace:<br>" . get_class($thrown) . "</h1>";
    echo "<div class='error'>";
    if (method_exists($thrown, 'getMessage')) {
        echo "<strong>" . htmlentities($thrown->getMessage()) . "</strong>";
    }
    echo '<br>';
    if (method_exists($thrown, 'getFile') && method_exists($thrown, 'getLine')) {
        echo $trim($thrown->getFile()) . ':' . $thrown->getLine();
    }
    echo "</div>";
    if (method_exists($thrown, 'getTrace')) {
        echo "<div class='stack-trace'>";
        foreach ($thrown->getTrace() as $t) {
            echo "<div>";
            if (@$t['file']) {
                echo "<strong>" . htmlentities($trim(@$t['file'])) . ":" . @$t['line'] . "</strong><br>";
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
                        echo '&nbsp;' . $arg;
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
    echo "</section>";
}
