<?php

use Phinx\Migration\AbstractMigration;

class RenameMediaPageColumn extends AbstractMigration
{
    public function change()
    {
        $this->table('rich_media')
            ->renameColumn('page_uuid', 'parent')
            ->save();
    }
}
