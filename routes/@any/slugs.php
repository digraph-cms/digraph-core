<?php
$package->cache_noStore();
$f = $cms->helper('forms');
$s = $cms->helper('slugs');
$n = $cms->helper('notifications');
$token = $cms->helper('session')->getToken('slug.delete');
$noun = $package->noun();

//do deletions
if ($delete = $package['url.args.delete']) {
    if ($delete = json_decode($delete, true)) {
        list($url, $noun) = $delete;
        if ($package['url.args.hash'] == md5($token.$url.$noun)) {
            if ($s->delete($url, $noun)) {
                $n->flashConfirmation("Deleted URL <code>$url =&gt; $noun</code>");
            }
        } else {
            $n->flashError('Incorrect link hash, please try again');
        }
    }
    $url = $package->url();
    unset($url['args.delete']);
    unset($url['args.hash']);
    $package->redirect($url->string(true));
    return;
}

/**
 * form for adding new URLs and updating saved URL patterns
 */
$form = $f->form('Add a URL for this noun', 'slug');
$form->addClass('compact-form');
$form['slug'] = $f->field('digraph_slug', 'URL pattern');
$form['slug']->required();
$form['slug']->default($noun['digraph.slugpattern']);
$form['slug']['use']->addClass('hidden');
$form['update'] = $f->field('checkbox', 'Save pattern to automatically update');
$form['update']->default(true);
echo $form;
//create slug
if ($form->handle()) {
    $pattern = $form['slug']['slug']->value();
    $slug = $s->createFromPattern($pattern, $noun);
    if ($s->create($slug, $noun['dso.id'])) {
        $n->confirmation('Added "'.$noun->name().'" is now accessible at '.$cms->config['url.base'].$slug);
    }
    //update pattern in noun
    if ($form['update']->value()) {
        $noun['digraph.slugpattern'] = $pattern;
        if ($noun->update(true)) {
            $n->confirmation('Changed URL pattern of "'.$noun->name().'" to <code>'.$pattern.'</code>');
        }
    }
}
/**
 * List existing slugs for noun
 */
echo '<h3>URLs currently associated with this noun</h3>';
$slugs = $s->slugs($noun['dso.id']);
$url = $cms->config['url.base'];
echo "<table>";
foreach ($slugs as $slug) {
    $durl = $package->url();
    $durl['args.delete'] = json_encode([$slug, $noun['dso.id']]);
    $durl['args.hash'] = md5($token.$slug.$noun['dso.id']);
    echo "<tr>";
    echo "<td><a href='$url$slug'>$slug</a></td>";
    echo "<td><a href='".$durl->string(true)."' class='row-button row-delete'>delete</a></td>";
    echo "</tr>";
}
echo "</table>";
