<?php
$search = $cms->helper('search');
$t = $cms->helper('templates');

$form = $search->form();
echo $form;

echo $cms->helper('paginator')->paginate(
    $search->search($package['url.args.search_q']),//things to paginate
    $package,//package (to get url/arguments from)
    'page',//argument to use for page
    $cms->config['search.perpage'],//items per page
    function ($e) use ($t) {//callback given elements
        return $t->render('digraph/search-result.twig', ['result'=>$e]);
    }
);
