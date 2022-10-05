<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class EmailAddSentColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('email_log')
            ->rename('email')
            ->addColumn('sent', 'integer', ['null' => true, 'after' => 'uuid'])
            ->addIndex('sent')
            ->save();
        $this->execute('UPDATE `email` SET `sent` = `time`');
    }
}
