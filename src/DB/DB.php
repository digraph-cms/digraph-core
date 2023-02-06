<?php

namespace DigraphCMS\DB;

use DigraphCMS\Config;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Users\Permissions;
use Envms\FluentPDO\Query;
use Exception;
use PDO;
use PDOException;

DB::addPhinxPath(__DIR__ . '/../../phinx');
Dispatcher::addSubscriber(DB::class);

class DB
{
    /** @var PDO|null */
    protected static $pdo;
    /** @var string|null */
    protected static $driver;
    /** @var Query|null */
    protected static $query;
    /** @var array<int,string> */
    protected static $phinxPaths = [];
    /** @var int */
    protected static $transactions = 0;

    const NESTED_TRANSACTION_SUPPORT = [];

    public static function onDbExpandJsonPath_sqlite(string $path, string $column, string $table): string
    {
        $table = $table ? "`$table`." : '';
        return "JSON_VALUE($table`$column`,'$.{$path}')";
    }

    public static function onDbExpandJsonPath_mysql(string $path, string $column, string $table): string
    {
        $table = $table ? "`$table`." : '';
        return "JSON_UNQUOTE(JSON_EXTRACT($table`$column`,'$.{$path}'))";
    }

    public static function onException_PDOException(PDOException $exception): ?bool
    {
        switch ($exception->getMessage()) {
            case 'SQLSTATE[HY000]: General error: 5 database is locked':
                Digraph::buildErrorContent(503, 'Database is locked for writing or maintenance, please try again in a moment');
                return true;
            default:
                if (Permissions::inGroup('admins')) Digraph::buildErrorContent(500, 'Database error: ' . $exception->getMessage());
                else Digraph::buildErrorContent(500, 'Database error');
                return true;
        }
    }

    public static function beginTransaction(): void
    {
        static::$transactions++;
        if (in_array(Config::get('db.adapter'), static::NESTED_TRANSACTION_SUPPORT) || !static::pdo()->inTransaction()) {
            static::pdo()->beginTransaction();
        }
    }

    public static function commit(): void
    {
        static::$transactions--;
        if (in_array(Config::get('db.adapter'), static::NESTED_TRANSACTION_SUPPORT) || (static::$transactions == 0 && static::pdo()->inTransaction())) {
            static::pdo()->commit();
        }
    }

    public static function rollback(): void
    {
        static::$transactions--;
        if (in_array(Config::get('db.adapter'), static::NESTED_TRANSACTION_SUPPORT) || (static::$transactions == 0 && static::pdo()->inTransaction())) {
            static::pdo()->rollBack();
        }
    }

    /**
     * @return array<int,string>
     */
    public static function migrationPaths(): array
    {
        return array_filter(array_map(
            function ($e) {
                return realpath($e . '/migrations');
            },
            self::$phinxPaths
        ));
    }

    /**
     * @return array<int,string>
     */
    public static function seedPaths(): array
    {
        return array_filter(array_map(
            function ($e) {
                return realpath($e . '/seeds');
            },
            self::$phinxPaths
        ));
    }

    public static function addPhinxPath(string $path): void
    {
        array_unshift(self::$phinxPaths, $path);
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            try {
                // set up database
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
                        self::$pdo->setAttribute(PDO::ATTR_TIMEOUT, 10);
                        // after setting up remove password from config so it's harder to exfiltrate
                        Config::set('db.pass', false);
                        break;
                    default:
                        throw new \Exception("Unsupported DB adapter " . Config::get('db.adapter'));
                }
            } catch (\Throwable $th) {
                if ($th instanceof Exception) {
                    throw new DBConnectionException("Error setting up PDO: " . $th->getMessage());
                } else {
                    throw new DBConnectionException("Error setting up PDO: " . get_class($th));
                }
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
            default:
                return (Config::get('db.adapter_dsn') ?? Config::get('db.adapter')) . ':' .
                    implode(';', array_filter([
                        'host=' . Config::get('db.host'),
                        Config::get('db.port') ? 'port=' . Config::get('db.port') : false,
                        Config::get('db.name') ? 'dbname=' . Config::get('db.name') : false,
                        Config::get('db.charset') ? 'charset=' . Config::get('db.charset') : false
                    ]));
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
            self::$query->convertWriteTypes(true);
        }
        return self::$query;
    }
}
