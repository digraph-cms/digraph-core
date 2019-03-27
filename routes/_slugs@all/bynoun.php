<?php
$package->noCache();
$f = $cms->helper('forms');
$s = $cms->helper('slugs');
$n = $cms->helper('notifications');

if ($package['url.args.noun']) {
    $noun = $cms->read($package['url.args.noun']);
    echo '<h2>Managing URLs for '.$noun->name().'</h2>';
    /**
     * Form for adding a URL to this noun
     */
    $slugForm = $f->form('Add a URL for this noun', 'slug');
    $slugForm->addClass('compact-form');
    $slugForm['slug'] = $f->field('digraph_slug', 'URL pattern');
    $slugForm['slug']->required();
    $slugForm['slug']['use']->addClass('hidden');
    $slugForm['update'] = $f->field('checkbox', 'Save pattern to automatically update');
    $slugForm['update']->default(true);
    echo $slugForm;
    //create slug
    if ($slugForm->handle()) {
        $pattern = $slugForm['slug']['slug']->value();
        $slug = $s->createFromPattern($pattern, $noun);
        if ($s->create($slug, $noun['dso.id'])) {
            $n->confirmation('Added "'.$noun->name().'" is now accessible at '.$cms->config['url.base'].$slug);
        }
        //update pattern in noun
        if ($slugForm['update']->value()) {
            $noun['digraph.slugpattern'] = $pattern;
            if ($noun->update(true)) {
                $n->confirmation('Changed URL pattern of "'.$noun->name().'" to <code>'.$pattern.'</code>');
            }
        }
    }
    /**
     * List existing slugs for noun
     */
    echo '<h3>Current URLs</h3>';
    $slugs = $s->slugs($noun['dso.id']);
    $url = $cms->config['url.base'];
    echo "<table>";
    foreach ($slugs as $slug) {
        echo "<tr>";
        echo "<td>$url$slug</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<hr>";
    echo "<h2>Search again</h2>";
}

/**
 * Form for locating a noun
 */
$nounForm = $f->form('Search for a noun', 'noun');
$nounForm->addClass('compact-form');
$nounForm['noun'] = $f->field('noun', 'Search for a noun to view/manage its URLs');
$nounForm['noun']->required();
$nounForm['noun']->default($package['url.args.noun']);
echo $nounForm;
if ($nounForm->handle()) {
    $package['url.args.noun'] = $nounForm['noun']->value();
    $package->redirect($package->url());
}
