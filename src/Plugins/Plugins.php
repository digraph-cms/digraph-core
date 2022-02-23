<?php

namespace DigraphCMS\Plugins;

use DigraphCMS\Config;
use DigraphCMS\Content\Router;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Cache\CacheableState;
use DigraphCMS\Cache\CachedInitializer;
use DigraphCMS\Media\Media;
use DigraphCMS\UI\Templates;
use DirectoryIterator;

class Plugins
{
    protected static $plugins = [];

    public static function loadFromDirectory(string $directory)
    {
        if (!is_dir($directory)) {
            return;
        }
        foreach (new DirectoryIterator($directory) as $f) {
            if (!$f->isDir() || $f->isDot()) {
                continue;
            }
            $path = $f->getRealPath();
            if (is_dir($path)) {
                static::load($path, true);
            }
        }
    }

    /**
     * Parse a composer lock file and load all plugins found in it. Specifically
     * this looks for any composer packages with the type "digraph-plugin"
     * 
     * Defaults to a vendor directory of "vendor" in the same folder as the lock
     * file, but this can be overridden.
     *
     * @param string $composerLockFile
     * @param string $vendorDirectory path to vendor directory, relative to lock file
     * @return void
     */
    public static function loadFromComposer(string $composerLockFile, string $vendorDirectory = 'vendor')
    {
        if (!is_file($composerLockFile)) {
            return;
        }
        $vendorDirectory = realpath(dirname($composerLockFile) . '/' . $vendorDirectory);
        CachedInitializer::run(
            'plugins/composer/' . md5_file($composerLockFile),
            function (CacheableState $state) use ($composerLockFile, $vendorDirectory) {
                $data = Config::parseJsonFile($composerLockFile);
                foreach ($data['packages'] as $package) {
                    if ($package['type'] == 'digraph-plugin') {
                        $directory = $vendorDirectory . '/' . $package['name'];
                        $state[md5($directory)] = $directory;
                    }
                }
            },
            function (CacheableState $state) {
                foreach ($state as $pluginDirectory) {
                    static::load($pluginDirectory);
                }
            }
        );
    }

    /**
     * Register an autoloader for the given namespace and directory. Both should
     * not have trailing slashes.
     *
     * @param string $namespace
     * @param string $directory
     * @return void
     */
    public static function autoloader(string $namespace, string $directory)
    {
        $namespace = preg_replace('/\\$/', '', $namespace) . '\\';
        $directory = realpath($directory) . '/';
        spl_autoload_register(function ($class) use ($namespace, $directory) {
            // check if class is in this namespace
            $len = strlen($namespace);
            if (strncmp($namespace, $class, $len) !== 0) {
                return;
            }
            // turn class into a filename
            $file = $directory . str_replace('\\', '/', substr($class, $len)) . '.php';
            // include file exists
            if (file_exists($file)) {
                require $file;
            }
        });
    }

    public static function load(string $pluginDirectory, $generateAutoloader = false)
    {
        CachedInitializer::run(
            'plugins/load/' . md5($pluginDirectory),
            function (CacheableState $state) use ($pluginDirectory, $generateAutoloader) {
                // merge plugin config
                if (is_file($pluginDirectory . '/config.yaml')) {
                    $state->mergeConfig(Config::parseYamlFile($pluginDirectory . '/config.yaml'));
                }
                // get plugin class and queue it for creation
                $pluginFile = $pluginDirectory . '/src/Plugin.php';
                $match = null;
                if (!preg_match('/namespace (.+);/', file_get_contents($pluginFile), $match)) {
                    throw new \Exception("Error parsing namespace from Plugin " . $pluginFile);
                }
                $namespace = $match[1];
                $class = $namespace . '\\Plugin';
                $state['classes.' . md5($class)] = $class;
                // queue autoloader generation if requested
                if ($generateAutoloader) {
                    $state['autoloaders.' . md5($class)] = [
                        $pluginDirectory . '/src',
                        $namespace
                    ];
                }
            },
            function (CacheableState $state) {
                // configure autoloaders
                foreach ($state['autoloaders'] ?? [] as $al) {
                    static::autoloader($al[1], $al[0]);
                }
                // instantiate and register plugins
                foreach ($state['classes'] as $class) {
                    $plugin = new $class;
                    static::register($plugin);
                    if ($plugin instanceof AbstractInitializedPlugin) {
                        CachedInitializer::run(
                            'plugins/initialization/' . md5($class),
                            [$plugin, 'initialize_preCache'],
                            [$plugin, 'initialize_postCache']
                        );
                    }
                }
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
        // set up template directories
        foreach ($plugin->templateFolders() as $dir) {
            Templates::addSource($dir);
        }
        // set up phinx folders
        foreach ($plugin->phinxFolders() as $dir) {
            DB::addPhinxPath($dir);
        }
        // call post-registration hook
        $plugin->registered();
    }
}
