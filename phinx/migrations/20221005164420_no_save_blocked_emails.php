<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NoSaveBlockedEmails extends AbstractMigration
{
    public function change(): void
    {
        $this->table('email')
            ->removeIndex('blocked')
            ->removeColumn('blocked')
            ->save();
    }
}
