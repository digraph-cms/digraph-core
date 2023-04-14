<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class EmailAddSentColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('email')
            ->addColumn('sent', 'biginteger', ['signed' => false, 'null' => true, 'after' => 'uuid'])
            ->addIndex('sent')
            ->save();
        $this->execute('UPDATE `email` SET `sent` = `time`');
    }
}
