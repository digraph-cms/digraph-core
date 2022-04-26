<h1>Refresh URL slugs</h1>
<p>
    This tool allows you to refresh the slugs of a page and every child underneath it.
    This is useful in cases where you've changed the URL of a page with many children, and would like to update the URLs of all of its children to match.
    Previous URLs are always retained by default, and will redirect seamlessly to the new ones.
</p>

<?php

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Slugs;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\Cron\RecursivePageJob;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\PageField;
use DigraphCMS\HTML\Forms\FormWrapper;

$form = new FormWrapper();
$form->setAttribute('data-target', '_frame');

$page = (new PageField('Start page'))
    ->setRequired(true);
$form->addChild($page);

echo '<div id="refresh-slugs-interface" class="navigation-frame navigation-frame--stateless" data-target="_top">';
if ($form->ready()) {
    $job = new RecursivePageJob(
        $page->value(),
        function (DeferredJob $job, AbstractPage $page) {
            if (!$page->slugPattern()) return $page->uuid() . ": No slug pattern";
            Slugs::setFromPattern($page, $page->slugPattern());
            return $page->uuid() . " slug set to " . $page->slug();
        }
    );
    echo new DeferredProgressBar($job->group());
} else {
    echo $form;
}
echo '</div>';
