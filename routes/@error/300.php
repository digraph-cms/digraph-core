<?php
$package['fields.page_name'] = 'Multiple options';
?>
<p>This URL can be resolved to more than one destination. Please choose an option below:</p>
<?php
echo "<ul>";
foreach ($package['response.300'] as $i) {
    $object = $cms->read($i['object']);
    $objectLink = $object->url()->html(null, true)->string();
    echo "<li><strong>{$object['dso.type']} #{$object['dso.id']}</strong>: ";
    if ($objectLink != $i['link']) {
        echo "<em>{$objectLink}</em><br>{$i['link']}</li>";
    } else {
        echo "<br>{$i['link']}</li>";
    }
}
echo "</ul>";
