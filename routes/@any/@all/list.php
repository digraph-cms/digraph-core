<?php
$search = $this->factory()->search();
$search->where('${dso.type} = :type');

$results = $search->execute(['type'=>$this->package->url()['noun']]);

echo '<ul>';
foreach ($results as $dso) {
    echo '<li>'.$dso->url()->html().'</li>';
}
echo '</ul>';
