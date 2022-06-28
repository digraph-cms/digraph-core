<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class SearchIndex extends AbstractMigration
{
    public function change(): void
    {
        $this->table('search_index', ['engine' => 'InnoDB'])
            ->addColumn('url', 'string', ['length' => 250])
            ->addColumn('title', 'string', ['length' => 250])
            ->addColumn('body', 'text', ['length' => MysqlAdapter::TEXT_MEDIUM])
            ->addIndex('body', ['type' => 'fulltext'])
            ->addIndex('url', ['unique' => true])
            ->create();
    }
}
