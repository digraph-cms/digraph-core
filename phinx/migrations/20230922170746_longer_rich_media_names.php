<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LongerRichMediaNames extends AbstractMigration
{
    public function change(): void
    {
        $this->table('rich_media')
            ->changeColumn('name', 'string', ['length' => 250, 'null' => false])
            ->save();
    }
}
