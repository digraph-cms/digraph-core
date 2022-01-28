<?php

use DigraphCMS\UI\Theme;

$variables = Theme::variables('light');

foreach ($variables as $name => $value) {
    if (preg_match("/^(#[0-9a-f]{6}|rgba\([0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0-9\.]+\))$/i", $value)) {
        if (preg_match('/-inv$/',$name)) {
            continue;
        }
        printf(
            '<div style="padding:1em;background:var(--%s);color:var(--%s-inv)">%s</div>',
            $name,
            $name,
            $name
        );
    }else {
        printf(
            '<div><code>%s: %s</code></div>',
            $name,
            $value
        );
    }
}
