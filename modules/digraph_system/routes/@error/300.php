<h1>Multiple options</h1>
<p>This URL can be resolved to more than one destination. Please choose an option below:</p>
<?php
foreach ($package['temp.300options'] as $opt) {
    echo "<li>".$opt[0]->url($opt[1], $package->url()['args'], true)->html()."</li>";
}
