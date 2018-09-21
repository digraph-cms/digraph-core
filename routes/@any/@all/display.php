<?php
$search = $this->cms()->factory()->search();
$search->where('${dso.type} = :type');
$search->order('${dso.modified.date} desc');

foreach ($search->execute(['type'=>$package->url()['noun']]) as $dso) {
    $link = $dso->url()->html();
    echo "<div>$link</div>";
}
