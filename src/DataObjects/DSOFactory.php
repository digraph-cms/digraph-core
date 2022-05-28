<?php

namespace DigraphCMS\DataObjects;

use Destructr\Factory;

class DSOFactory extends Factory
{
    const ID_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    const ID_LENGTH = 8;
    const SCHEMA = [];
    protected $schema = [];

    public function __construct(string $type)
    {
        $this->table = 'dso_' . $type;
        $this->driver = DataObjects::driver();
        $this->schema = static::buildSchema($type);
    }

    public function class(?array $data): ?string
    {
        return DSOObject::class;
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
                    'type' => 'VARCHAR(16)', //column type
                    'index' => 'BTREE', //whether/how to index
                    'unique' => true, //whether column should be unique
                    'primary' => true, //whether column should be the primary key
                ],
                'dso.created.date' => [
                    'name' => 'dso_created_date',
                    'type' => 'BIGINT',
                    'index' => 'BTREE',
                ],
                'dso.modified.date' => [
                    'name' => 'dso_modified_date',
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
}
