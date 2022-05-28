<h1>Update database schema</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\DataObjects\DataObjects;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

$job = Context::arg('job');
if ($job) {
    echo (new DeferredProgressBar($job))
        ->setDisplayAfter('Updated data object table schemas');
    return;
}

echo "<p>Use this tool to force an update of your data object tables to match their specified schema. This may improve performance after updates.</p>";
Notifications::printWarning("Back up your database before running this tool. Data loss is rare, but is a possibility. Especially if using third-party plugins that modify data object schemas.");

echo new SingleButton('Update data object database', function () {
    $job = DataObjects::updateAllEnvironments();
    throw new RedirectException(new URL('?job=' . $job->group()));
});
