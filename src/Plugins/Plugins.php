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
    protected static $plugins = [];

    public static function loadFromComposer(string $composerLockFile, string $vendorDirectory = 'vendor')
    {
        if (!is_file($composerLockFile)) {
            return;
        }
        $vendorDirectory = realpath(dirname($composerLockFile) . '/' . $vendorDirectory);
        Initializer::run(
            'plugins/composer/' . md5_file($composerLockFile),
            function (InitializationState $state) use ($composerLockFile, $vendorDirectory) {
                $data = json_decode(file_get_contents($composerLockFile), true);
                foreach ($data['packages'] as $package) {
                    if ($package['type'] == 'digraph-plugin') {
                        $directory = $vendorDirectory . '/' . $package['name'];
                        $state[md5($directory)] = $directory;
                    }
                }
            },
            function (InitializationState $state) {
                foreach ($state as $pluginDirectory) {
                    static::load($pluginDirectory, true);
                }
            }
        );
    }

    public static function load(string $pluginDirectory, $skipAutoloader = false)
    {
        Initializer::run(
            'plugins/load/' . md5($pluginDirectory),
            function (InitializationState $state) use ($pluginDirectory) {
                // merge plugin config
                if (is_file($pluginDirectory . '/config.yaml')) {
                    $state->mergeConfig(Config::parseYamlFile($pluginDirectory . '/config.yaml'));
                }
                // get plugin class
                $pluginFile = $pluginDirectory.'/src/Plugin.php';
                $match = null;
                if (!preg_match('/namespace (.+);/',file_get_contents($pluginFile),$match)) {
                    throw new \Exception("Error parsing namespace from Plugin ".$pluginFile);
                }
                var_dump($match);
            },
            function (InitializationState $state) {
            }
        );
    }

    public static function plugins(): array
    {
        return static::$plugins;
    }

    public static function register(AbstractPlugin $plugin)
    {
        static::$plugins[$plugin->name()] = $plugin;
        // subscribe to events
        Dispatcher::addSubscriber($plugin);
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
    }
}
