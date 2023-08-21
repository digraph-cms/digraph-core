<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LongerFilestoreFilenames extends AbstractMigration
{
    public function change(): void
    {
        $this->table('filestore')
            ->changeColumn('filename', 'string', ['length' => 250, 'null' => false])
            ->save();
    }
}