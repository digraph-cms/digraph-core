<?php

namespace DigraphCMS\DataObjects;

use Destructr\DSOInterface;
use Destructr\Factory;
use Destructr\Search;
use DigraphCMS\Session\Session;

class DSOFactory extends Factory
{
    const ID_CHARS = 'abcdefghijklmnopqrstuvwxyz123456789';
    const ID_LENGTH = 10;
    const SCHEMA = [];
    protected $schema = [];

    public function __construct(string $type)
    {
        $this->table = 'dso_' . $type;
        $this->driver = DataObjects::driver();
        $this->schema = static::buildSchema($type);
    }

    public function get(string $uuid): ?DSOObject
    {
        $result = $this->query()
            ->where('${dso.id} = ?', [$uuid])
            ->limit(1)
            ->fetchAll();
        return array_shift($result);
    }

    public function class(?array $data): ?string
    {
        return DSOObject::class;
    }

    protected function hook_create(DSOInterface $dso)
    {
        if (!$dso->get('dso.id')) {
            $dso->set('dso.id', static::generate_id(static::ID_CHARS, static::ID_LENGTH), true);
        }
        if (!$dso->get('dso.created.date')) {
            $dso->set('dso.created.date', time());
        }
        if (!$dso->get('dso.created.user')) {
            $dso->set('dso.created.user', Session::uuid());
        }
    }

    protected function hook_update(DSOInterface $dso)
    {
        $dso->set('dso.updated.date', time());
        $dso->set('dso.updated.user', Session::uuid());
    }

    /**
     * Return the schema that should be used for this factory. Generally when extending
     * this class the schema should be defined in the constant self::SCHEMA
     *
     * @param string $type
     * @return array
     */
    protected static function buildSchema(string $type): array
    {
        return array_merge(
            static::SCHEMA,
            [
                'dso.id' => [
                    'name' => 'dso_id', //column name to be used
                    'type' => 'VARCHAR(32)', //column type
                    'index' => 'BTREE', //whether/how to index
                    'unique' => true, //whether column should be unique
                    'primary' => true, //whether column should be the primary key
                ],
                'dso.created.date' => [
                    'name' => 'dso_created_date',
                    'type' => 'BIGINT',
                    'index' => 'BTREE',
                ],
                'dso.updated.date' => [
                    'name' => 'dso_updated_date',
                    'type' => 'BIGINT',
                    'index' => 'BTREE',
                ],
                'dso.deleted' => [
                    'name' => 'dso_deleted',
                    'type' => 'BIGINT',
                    'index' => 'BTREE',
                ]
            ]
        );
    }

    public function query(): DSOQuery
    {
        return new DSOQuery($this);
    }

    /**
     * @deprecated use query() instead
     * @return Search
     */
    public function search(): Search
    {
        return parent::search();
    }
}
