<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class FilestorePermissionsColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('filestore')
            ->addColumn('permissions', 'text', ['length' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
            ->update();
    }
}