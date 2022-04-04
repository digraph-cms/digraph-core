<?php

use Phinx\Migration\AbstractMigration;

class RenameMediaPageColumn extends AbstractMigration
{
    public function change()
    {
        $this->table('rich_media')
            ->removeIndex('page_uuid')
            ->renameColumn('page_uuid', 'parent')
            ->addIndex('parent')
            ->save();
    }
}
