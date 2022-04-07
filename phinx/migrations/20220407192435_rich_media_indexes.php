<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RichMediaIndexes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('rich_media')
            ->addIndex('parent')
            ->addIndex('uuid')
            ->addIndex('class')
            ->addIndex('created')
            ->addIndex('created_by')
            ->addIndex('updated')
            ->addIndex('updated_by')
            ->save();
    }
}
