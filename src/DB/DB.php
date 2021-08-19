<?php

namespace DigraphCMS\DB;

use DigraphCMS\Config;
use Envms\FluentPDO\Query;
use PDO;

DB::addMigrationPath(__DIR__ . '/../../phinx');

class DB
{
    protected static $pdo, $driver, $query;
    protected static $migrationPaths = [];
    protected static $transactions = 0;

    public static function beginTransaction()
    {
        static::$transactions++;
        if (!static::pdo()->inTransaction()) {
            static::pdo()->beginTransaction();
        }
    }

    public static function commit()
    {
        static::$transactions--;
        if (static::$transactions == 0) {
            static::pdo()->commit();
        }
    }

    public static function migrationPaths(): array
    {
        return self::$migrationPaths;
    }

    public static function addMigrationPath(string $path)
    {
        array_unshift(self::$migrationPaths, $path);
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            try {
                switch (Config::get('db.adapter')) {
                    case 'sqlite':
                        self::$pdo = new PDO(
                            Config::get('db.dsn') ?? self::buildDSN(),
                            null,
                            null,
                            Config::get('db.pdo_options')
                        );
                        if (Config::get('db.sqlite.create_functions')) {
                            SqliteShim::createFunctions(self::$pdo);
                        }
                        break;
                    case 'mysql':
                        self::$pdo = new PDO(
                            Config::get('db.dsn') ?? self::buildDSN(),
                            Config::get('db.user'),
                            Config::get('db.pass'),
                            Config::get('db.pdo_options')
                        );
                        self::$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                        break;
                    default:
                        throw new \Exception("Unsupported DB adapter " . Config::get('db.adapter'));
                        break;
                }
            } catch (\Throwable $th) {
                throw new \Exception("Error setting up PDO: " . $th->getMessage());
            }
            // throw exceptions on PDO errors
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // save driver string
            self::$driver = Config::get('db.adapter');
        }
        return self::$pdo;
    }

    protected static function buildDSN(): string
    {
        switch (Config::get('db.adapter')) {
            case 'sqlite':
                return 'sqlite:' . Config::get('db.name') . '.sqlite3';
                break;
            default:
                return (Config::get('db.adapter_dsn') ?? Config::get('db.adapter')) . ':' .
                    implode(';', array_filter([
                        'host=' . Config::get('db.host'),
                        Config::get('db.port') ? 'port=' . Config::get('db.port') : false,
                        Config::get('db.name') ? 'dbname=' . Config::get('db.name') : false,
                        Config::get('db.charset') ? 'charset=' . Config::get('db.charset') : false
                    ]));
                break;
        }
    }

    public static function driver(): string
    {
        self::pdo();
        return self::$driver;
    }

    public static function query(): Query
    {
        if (!self::$query) {
            self::$query = new Query(self::pdo());
        }
        return self::$query;
    }
}
