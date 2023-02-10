<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

/**
 * The purpose of this change is ensure that the job column is large enough to
 * hold large amounts of data. In particular this was becoming a problem for
 * search indexing jobs with large amounts of page content.
 */
final class ExpandBackgroundJobColumns extends AbstractMigration
{
    public function change(): void
    {
        $this->table('defex')
            ->changeColumn('job', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM])
            ->save();
        $this->table('cron')
            ->changeColumn('job', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM])
            ->save();
    }
}
