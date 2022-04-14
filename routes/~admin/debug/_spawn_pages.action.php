<h1>Mock page spawner</h1>
<p>
    Use this tool to spawn a large number of child pages below a given page, optionally nested to a given depth.
</p>
<?php

use DigraphCMS\Content\Graph;
use DigraphCMS\Content\Page;
use DigraphCMS\Cron\Deferred;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\PageField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\RichContent\RichContent;

$form = new FormWrapper();

$parent = (new PageField('Parent page'))
    ->setRequired(true);
$form->addChild($parent);

$count = (new Field('Number of children to spawn'))
    ->setDefault(100)
    ->setRequired(true);
$count->input()->setAttribute('type', 'number');
$form->addChild($count);

$depth = (new Field('Maximum nesting depth'))
    ->setDefault(5)
    ->setRequired(true);
$depth->input()->setAttribute('type', 'number');
$form->addChild($depth);

if ($form->ready()) {
    $parent = $parent->value();
    $count = $count->value();
    $depth = $depth->value();
    $job = new DeferredJob(function (DeferredJob $job) use ($parent, $count, $depth) {
        // first order of business is to spawn copies
        while (Deferred::groupCount($job->group()) < $count) $job->spawnClone();
        // next traverse up to $depth-1 random links from $parent and spawn a page there
        $depth = random_int(0, $depth - 1);
        while ($depth >= 0 && $child = Graph::randomChildID($parent)) {
            $parent = $child;
            $depth--;
        }
        $page = new Page();
        $page->name('Mock page ' . $page->uuid());
        $page->richContent('body', new RichContent('# Mock page ' . $page->uuid()));
        DB::beginTransaction();
        Graph::insertLink($parent, $page->uuid());
        $page->insert();
        DB::commit();
        return 'Created ' . $page->url();
    });
    $job->insert();
    echo new DeferredProgressBar($job->group(), 'Creating pages');
} else {
    echo $form;
}
