<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SearchIndexExpiration extends AbstractMigration
{

    public function change(): void
    {
        $this->table('search_index')
            ->addColumn('updated', 'integer')
            ->addIndex('updated')
            ->update();
    }
}
