<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SlugArchiveColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('page_slug')
            ->addColumn('archive', 'boolean', ['default' => false])
            ->save();
    }
}
