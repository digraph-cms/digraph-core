<h1>Currently installed plugins</h1>

<p>
    Configuration, event listeners, and other side effects are applied in the order plugins are listed here.
</p>
<p>
    In general, expect plugins loaded via Composer to be loaded first, in the order they appear in <code>composer.lock</code>.
    That order should reflect their order in <code>composer.json</code>, or their order in the dependency graph if they are a dependency.
</p>
<p>
    Plugins loaded from a directory will be loaded in alphabetical order by directory name.
    Plugins should not depend on having a specific directory name, so they can be renamed to achieve a specific load order if necessary.
</p>

<?php

use DigraphCMS\Config;
use DigraphCMS\HTML\Icon;
use DigraphCMS\Plugins\Plugins;

echo "<table>";
foreach (Plugins::plugins() as $name => $plugin) {
    echo "<tr>";
    echo "<th>$name</th>";
    echo "<td>";
    $path = $plugin->path();
    if (substr($path, 0, strlen(Config::get('paths.base'))) == Config::get('paths.base')) {
        $path = substr($path, strlen(Config::get('paths.base')) + 1);
    }
    echo "<code>$path</code>";
    echo "</td>";
    echo "<td>";
    echo $plugin->phinxFolders() ? new Icon('database', 'Alters database') : '';
    echo "</td>";
    echo "<td>";
    echo $plugin->routeFolders() ? new Icon('url', 'Provides routes') : '';
    echo "</td>";
    echo "<td>";
    echo $plugin->templateFolders() ? new Icon('template', 'Provides templates') : '';
    echo "</td>";
    echo "<td>";
    echo $plugin->mediaFolders() ? new Icon('media', 'Provides media files') : '';
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
