<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\DSO;

use Destructr\Drivers\AbstractDriver;
use Destructr\DSOInterface;
use Destructr\Factory;
use Destructr\Search;
use Digraph\CMS;
use Digraph\Logging\LogHelper;
use Flatrr\FlatArray;

class DigraphFactory extends Factory
{
    const ID_LENGTH = 16;
    protected $cms;
    protected $name = 'system';

    protected $schemaIsLegacy = false;

    protected $schema = [
        'dso.id' => [
            'name' => 'dso_id',
            'type' => 'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true,
            'primary' => true,
        ],
        'dso.type' => [
            'name' => 'dso_type',
            'type' => 'VARCHAR(30)',
            'index' => 'BTREE',
        ],
        'dso.deleted' => [
            'name' => 'dso_deleted',
            'type' => 'BIGINT',
            'index' => 'BTREE',
        ],
    ];

    public function updateEnvironment(): bool
    {
        //create downtime
        $downtime = $this->cms->factory('downtime')->create([
            'downtime.start' => time(),
            'digraph' => [
                'body' => [
                    'filter' => 'default',
                    'text' => 'A database schema update is in progress. This operation should be over shortly.',
                ],
                'name' => 'Factory ' . $this->name() . ' updateEnvironment',
            ],
        ]);
        if (!$downtime->insert()) {
            return false;
        }
        //execute
        $result = parent::updateEnvironment();
        //end downtime
        if ($result) {
            $this->cms->package()->saveLog(
                'schema update successful: ' . $this->name(),
                LogHelper::INFO,
                'updateEnvironmentSuccess.' . $this->name()
            );
            $downtime['downtime.end'] = time();
            $downtime->update();
            return true;
        } else {
            $this->cms->package()->saveLog(
                'schema update failed: ' . $this->name(),
                LogHelper::EMERGENCY,
                'updateEnvironmentFail.' . $this->name()
            );
            return false;
        }
    }

    public function name($set = null): string
    {
        if ($set) {
            $this->name = $set;
        }
        return $this->name;
    }

    public function addColumn(string $path, array $col)
    {
        // does nothing if we're using the legacy schema
        if ($this->schemaIsLegacy) {
            return;
        }
        // compute a decent column name from path, if necessary
        $col['name'] = @$col['name'] ?? preg_replace('/[^a-z]/', '_', strtolower($path));
        // remove existing duplicate column names from schema
        $this->schema = array_filter(
            $this->schema,
            function ($e) use ($col) {
                return $e['name'] != $col['name'];
            }
        );
        // add new column to schema
        $this->schema[$path] = $col;
    }

    public function __construct(AbstractDriver $driver, string $table)
    {
        parent::__construct($driver, $table);
        // we need to revert to the legacy schema if there is no schema
        // at all for this table in Destructr's schema management table
        // this way the correct legacy schema gets saved into it
        if (static::LEGACYSCHEMA) {
            if (!$this->driver->tableExists(AbstractDriver::SCHEMA_TABLE) || !$this->driver->getSchema($table)) {
                $this->schemaIsLegacy = true;
                $this->schema = static::LEGACYSCHEMA;
            }
        }
    }

    protected function hook_create(DSOInterface $dso)
    {
        parent::hook_create($dso);
        if (!isset($dso['dso.created.user.id'])) {
            if ($id = $this->cms->helper('users')->id()) {
                $dso['dso.created.user.id'] = $id;
            } else {
                $dso['dso.created.user.id'] = 'guest';
            }
        }
    }

    protected function hook_update(DSOInterface $dso)
    {
        parent::hook_update($dso);
        if ($id = $this->cms->helper('users')->id()) {
            $dso['dso.modified.user.id'] = $id;
        } else {
            $dso['dso.modified.user.id'] = 'guest';
        }
    }

    function class(?array $data): ?string
    {
        $data = new FlatArray($data);
        $type = $data['dso.type'];
        if (!$type || !$this->cms->config['types.' . $this->name . '.' . $type]) {
            $type = 'default';
        }
        if ($class = $this->cms->config['types.' . $this->name . '.' . $type]) {
            return $class;
        }
        throw new \Exception("No class could be found for factory " . $this->name . ", type " . $data['dso']['type'], 1);
    }

    public function cms(CMS $set = null): CMS
    {
        if ($set) {
            $this->cms = $set;
        }
        return $this->cms;
    }

    public function executeSearch(Search $search, array $params = array(), $deleted = false): array
    {
        //add deletion clause and expand column names
        $search = $this->preprocessSearch($search, $deleted);
        //run select
        $start = microtime(true);
        $r = $this->driver->select(
            $this->table,
            $search,
            $params
        );
        $duration = 1000 * (microtime(true) - $start);
        $this->cms->log('query took ' . $duration . 'ms');
        $this->cms->log('  ' . $search->where());
        foreach ($params as $key => $value) {
            $this->cms->log('  ' . $key . ' = ' . $value);
        }
        //return built list
        return $this->makeObjectsFromRows($r);
    }

    /**
     * This should never ever ever change, because it allows versions of Digraph
     * from before Destructr had schema management to be updated.
     */
    const LEGACYSCHEMA = [
        'dso.id' => [
            'name' => 'dso_id',
            'type' => 'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true,
            'primary' => true,
        ],
        'dso.type' => [
            'name' => 'dso_type',
            'type' => 'VARCHAR(30)',
            'index' => 'BTREE',
        ],
        'dso.deleted' => [
            'name' => 'dso_deleted',
            'type' => 'BIGINT',
            'index' => 'BTREE',
        ],
    ];
}
