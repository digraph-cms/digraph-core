<?php

use Formward\Fields\Checkbox;
use Formward\Fields\Input;

$package->cache_noStore();

/** @var \Digraph\Helpers\StaticHelper */
$helper = $cms->helper('static');

// check for delete calls
$url = $package->url();
if ($delete = $url->getData()['delete']) {
    $helper->delete($cms->helper('urls')->parse($delete));
    $package->redirect($cms->helper('urls')->parse('_controlpanel/static'));
    return;
}

// set up form for creating static pages
$form = $cms->helper('forms')->form('Make page static');
$form['url'] = new Input('URL to make static or refresh static copy of (relative to site root)');
$form['url']->required(true);

if ($form->handle()) {
    $url = $cms->helper('urls')->parse($form['url']->value());
    $helper->create($url);
    $package->redirect($cms->helper('urls')->parse('_controlpanel/static'));
    return;
}

echo $form;

// interface for static records
echo "<h2>Existing static records</h2>";
$existing = $helper->list();
echo "<table>";
echo "<tr><th>Path</th><th>Time</th><th>&nbsp;</th></tr>";
foreach ($existing as $path) {
    $file = $helper->path($cms->helper('urls')->parse($path));
    echo "<tr>";
    echo "<td>$path</td>";
    echo "<td>";
    if (file_exists($file)) {
        echo $cms->helper('strings')->dateHTML(filemtime($file));
    } else {
        echo "<em>missing</em>";
    }
    echo "</td>";
    /** @var Digraph\Urls\Url */
    $deleteURL = $package->url();
    $deleteURL->setData([
        'delete' => $path
    ]);
    echo "<td><a href='$deleteURL'>[delete]</a></td>";
    echo "</tr>";
}
echo "</table>";
