<?php
$package['fields.page_name'] = 'Content list';

$search = $this->cms()->factory()->search();
$search->order('${dso.modified.date} desc');

foreach ($search->execute() as $dso) {
    $link = $dso->url()->html();
    echo "<div>$link</div>";
}
