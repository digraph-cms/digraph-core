<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ConvertToUtf8Mb4 extends AbstractMigration
{

    public function up(): void
    {
        if ($this->getAdapter()->getAdapterType() !== 'mysql')
            return;
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        $rows = $this->fetchAll(
            "SELECT TABLE_NAME FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_TYPE = 'BASE TABLE'",
        );
        try {
            foreach ($rows as $row) {
                $t = $row[0];
                $this->execute("ALTER TABLE `{$t}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
            }
        }
        finally {
        }
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        if ($this->getAdapter()->getAdapterType() !== 'mysql')
            return;
        throw new \RuntimeException('Irreversible: reverting to utf8mb3 would truncate 4-byte characters.');
    }

}
