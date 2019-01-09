<?php
echo "<ul>";
foreach ($cms->config['factories'] as $name) {
    echo "<li>";
    echo $this->url('_trash', 'factory', ['factory'=>$name])->html($name);
    echo "</li>";
}
echo "</ul>";
