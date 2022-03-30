<?php

use DigraphCMS\Context;

echo "<table>";
foreach (Context::page()->metadata() as $k => $v) {
    if (is_array($v)) {
        $v = '<ul>' . implode('', array_map(
            function ($v) {
                return "<li>$v</li>";
            },
            $v
        )) . '</ul>';
    }
    printf('<tr><th>%s</th><td>%s</td></tr>', $k, $v);
}
echo "</table>";
