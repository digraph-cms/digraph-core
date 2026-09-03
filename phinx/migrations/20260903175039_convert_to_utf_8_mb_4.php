<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ConvertToUtf8Mb4 extends AbstractMigration
{

    private const TABLES = [
        'cron',
        'datastore',
        'defex',
        'email',
        'filestore',
        'page',
        'rich_media',
        'search_index',
        'user',
    ];

    public function up(): void
    {
        if ($this->getAdapter()->getAdapterType() !== 'mysql')
            return;
        $db = $this->getAdapter()->getOption('name');
        $this->execute("ALTER DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
        foreach (self::TABLES as $t) {
            $this->execute("ALTER TABLE `{$t}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
        }
    }

    public function down(): void
    {
        if ($this->getAdapter()->getAdapterType() !== 'mysql')
            return;
        foreach (self::TABLES as $t) {
            $this->execute("ALTER TABLE `{$t}` CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci");
        }
    }

}
