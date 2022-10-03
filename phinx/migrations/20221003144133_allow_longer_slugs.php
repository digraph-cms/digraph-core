<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AllowLongerSlugs extends AbstractMigration
{
    public function change(): void
    {
        $this->table('page_slug')
            ->changeColumn('url', 'string', ['length' => 250])
            ->save();
    }
}
