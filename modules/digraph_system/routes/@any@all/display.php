<?php
$package['fields.page_name'] = 'Content list';

$search = $this->cms()->factory()->search();
$search->where('${digraph.type} = :type');
$search->order('${dso.modified.date} desc');

foreach ($search->execute(['type'=>$package->url()['noun']]) as $dso) {
    $link = $dso->url()->html();
    echo "<div>$link</div>";
}
