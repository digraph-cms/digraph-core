<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class SearchIndex extends AbstractMigration
{
    public function change(): void
    {
        $this->table('search_index', ['engine' => 'InnoDB'])
            ->addColumn('owner', 'string', ['length' => 250, 'null' => false])
            ->addColumn('url', 'string', ['length' => 250, 'null' => false])
            ->addColumn('title', 'string', ['length' => 250, 'null' => false])
            ->addColumn('body', 'text', ['length' => MysqlAdapter::TEXT_MEDIUM, 'null' => false])
            ->addIndex('body', ['type' => 'fulltext'])
            ->addIndex('owner')
            ->addIndex('url', ['unique' => true])
            ->create();
    }
}
