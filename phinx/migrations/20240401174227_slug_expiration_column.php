<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SlugExpirationColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('page_slug')
            ->addColumn('expires', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('updated', 'biginteger', ['signed' => false, 'null' => false, 'default' => 0])
            ->addIndex('expires')
            ->addIndex('updated')
            ->update();
    }
}
