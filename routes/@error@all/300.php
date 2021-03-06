<?php
$package['fields.page_name'] = $package['fields.page_title'] = $package['url.text'] = 'Multiple options';
?>
<p>The requested URL can be resolved to more than one piece of content. Please choose an option below:</p>
<?php
echo "<ul>";
foreach ($package['response.300'] as $i) {
    if (@$i['object']) {
        $object = $cms->read($i['object']);
        $objectLink = $object->url()->html(null, true)->string();
        echo "<li><strong>Content ID #{$object['dso.id']}</strong>: ";
        if ($objectLink != $i['link']) {
            echo "<em>{$objectLink}</em><br>{$i['link']}</li>";
        } else {
            echo "<br>{$i['link']}</li>";
        }
    } else {
        echo "<li>{$i['link']}</li>";
    }
}
echo "</ul>";
