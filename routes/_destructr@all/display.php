<?php
use Destructr\Drivers\SQLiteDriver;
use Digraph\Forms\ConfirmationForm;
use Formward\Fields\File;
use Formward\Form;

$package->cache_noStore();
$notifications = $cms->helper('notifications');
?>

<h2>Schema status</h2>
<?php
foreach ($cms->config['factories'] as $name) {
    $factory = $cms->factory($name);
    if ($factory->checkEnvironment()) {
        $notifications->printConfirmation("<strong>$name</strong> is up to date");
    } else {
        echo "<div class='digraph-card'>";
        $notifications->printWarning(
            "<p><strong>$name</strong> requires a schema update</p>" .
            "<p>Schema updates are dangerous and have the potential to leave your site in a broken state. You should back up the database before proceeding. The entire site will go offline during a schema update, but the operation should complete within a minute or two.</p>" .
            "<p>Once you begin the operation, please do not close this window, hit stop in your browser, or do anything to interrupt the process. Depending on your server configuration, doing so could break your database.</p>"
        );
        $form = new ConfirmationForm('Update ' . $name, "{$name}__update");
        $form->handle(function () use ($notifications, $factory, $name, $package) {
            if ($factory->updateEnvironment()) {
                $notifications->flashConfirmation('Updated schema for ' . $name);
                $package->redirect($package->url());
            } else {
                $notifications->error('Failed to update schema for ' . $name);
            }
        });
        echo $form;
        echo "</div>";
    }
}
//temporarily turning off backup tools, until they're done
return;
?>

<h2>Download backups</h2>
<?php
foreach ($cms->config['factories'] as $name) {
    $factory = $cms->factory($name);
    if ($factory->driver() instanceof SQLiteDriver) {
        echo "<div class='digraph-card'>";
        $form = new ConfirmationForm($name.': download backup', "{$name}__download");
        $form->handle(function () use ($notifications, $factory, $name, $package) {
            //TODO: do something
        });
        echo $form;
        echo "</div>";
    }else {
        //TODO: implement
        $notifications->printWarning("<p><strong>$name:</strong> currently only sqlite databases can be automatically downloaded with this tool</p>");
    }
}
?>

<h2>Restore backups</h2>
<?php
foreach ($cms->config['factories'] as $name) {
    $factory = $cms->factory($name);
    if ($factory->driver() instanceof SQLiteDriver) {
        echo "<div class='digraph-card'>";
        $form = new Form($name.': upload backup', "{$name}__upload");
        $form['file'] = new File('Backup file to restore');
        $form->handle(function () use ($notifications, $factory, $name, $package) {
            //TODO: do something
        });
        echo $form;
        echo "</div>";
    }else {
        //TODO: implement
        $notifications->printWarning("<p><strong>$name:</strong> currently only sqlite databases can be automatically restored with this tool</p>");
    }
}
?>