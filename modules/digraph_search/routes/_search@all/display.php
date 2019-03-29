<?php
$search = $cms->helper('search');
$t = $cms->helper('templates');

$form = $search->form();
echo $form;

foreach ($search->search($package['url.args.search_q']) as $result) {
    if ($result) {
        echo $t->render('digraph/search-result.twig', ['result'=>$result]);
    }
}
