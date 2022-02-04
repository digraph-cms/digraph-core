<?php

namespace DigraphCMS\Plugins;

use DigraphCMS\Config;
use DigraphCMS\Content\Router;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Initialization\InitializationState;
use DigraphCMS\Initialization\Initializer;
use DigraphCMS\Media\Media;

class Plugins
{
    protected $plugins = [];

    public static function loadFromComposer(string $composerLockFile)
    {
        if (!is_file($composerLockFile)) {
            return;
        }
        Initializer::run(
            'plugins/composer/' . md5_file($composerLockFile),
            function (InitializationState $state) use ($composerLockFile) {
                var_dump($composerLockFile);
            },
            function (InitializationState $state) {
            }
        );
    }

    public static function register(AbstractPlugin $plugin)
    {
        // subscribe to events if requested (otherwise not, to save performance)
        if ($plugin->isEventSubscriber()) {
            Dispatcher::addSubscriber($plugin);
        }
        // set up media directories
        foreach ($plugin->mediaFolders() as $dir) {
            Media::addSource($dir);
        }
        // set up route directories
        foreach ($plugin->routeFolders() as $dir) {
            Router::addSource($dir);
        }
        // set up phinx folders
        foreach ($plugin->phinxFolders() as $dir) {
            DB::addMigrationPath($dir);
        }
        // merge information into config
        Config::merge(
            [
                'class' => static::class,
                'config' => $plugin->initialConfig()
            ],
            "plugins." . $plugin->name(),
            false
        );
        // call post-registration method
        $plugin->postRegistrationCallback();
    }
}
