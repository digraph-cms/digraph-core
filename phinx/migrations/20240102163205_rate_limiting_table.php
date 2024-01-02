<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RateLimitingTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('rate_limit')
            ->addColumn('namespace', 'string', ['length' => 255])
            ->addColumn('name', 'string', ['length' => 255])
            ->addColumn('expires', 'integer', ['signed' => false])
            ->addIndex(['namespace', 'name'], ['unique' => true])
            ->addIndex(['expires'])
            ->create();
    }
}
