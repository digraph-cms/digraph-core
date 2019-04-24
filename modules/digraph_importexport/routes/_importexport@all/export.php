<?php
$f = $cms->helper('forms');

$nounForm = $f->form('', 'noun');
$nounForm->csrf(false);
$nounForm['noun'] = $f->field('noun', 'Search for a noun to export');
$nounForm['noun']->required();
$nounForm['noun']->default($package['url.args.noun']);
$nounForm['depth'] = $f->field('Formward\\Fields\\Number', 'Include children to depth');
$nounForm['depth']->addTip('Enter "-1" to include all children (may take a long time)');
$nounForm['depth']->addTip('Enter "0" to include no children');
$nounForm['depth']->default(-1);
$nounForm['depth']->required();

ini_set('max_execution_time', 0);

if ($nounForm->handle()) {
    $output = new \Flatrr\FlatArray();
    $output['digraph_export'] = $nounForm->value();
    //build list of all the nouns we need to include
    $nouns = [];
    recursivelyGetNouns(
        $cms->read($nounForm['noun']->value()),
        $nouns,
        $nounForm['depth']->value()
    );
    $nouns = array_map(
        function ($e) {
            return $e->get();
        },
        $nouns
    );
    $output['noun_ids'] = array_keys($nouns);
    $output['nouns'] = array_values($nouns);
    $output['digraph_export.results'] = count($output['nouns']);
    //see if helpers have hook_export method, and call it if they do
    foreach ($cms->allHelpers() as $name) {
        if (method_exists($cms->helper($name), 'hook_export')) {
            $output['helper.'.$name] = $cms->helper($name)->hook_export($output);
        }
    }
    //output value
    $package->makeMediaFile($nounForm['noun']->value().'_'.$nounForm['depth']->value().'.json');
    $package->binaryContent(json_encode($output->get()));
    // $package['response.disposition'] = 'attachment';
} else {
    echo $nounForm;
}

function recursivelyGetNouns($noun, &$list, $depth)
{
    global $cms;
    if (!$noun) {
        return;
    }
    $list[$noun['dso.id']] = $noun;
    if ($depth == 0) {
        return;
    } else {
        $depth--;
        foreach ($cms->helper('edges')->children($noun['dso.id']) as $c) {
            recursivelyGetNouns($cms->read($c), $list, $depth);
        }
    }
}
