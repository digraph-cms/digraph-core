<?php

declare(strict_types=1);

use DigraphCMS\Security\SecurityKeys;
use Phinx\Migration\AbstractMigration;

final class SecurityKeyTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('security_key')
            ->addColumn('key', 'string', ['length' => 250, 'null' => false])
            ->addColumn('created', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('expires', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('revoked', 'biginteger', ['signed' => false, 'null' => true])
            ->addIndex('key', ['unique' => true])
            ->addIndex('created')
            ->addIndex('expires')
            ->addIndex('revoked')
            ->insert([
                'key' => SecurityKeys::generateString(),
                'created' => time(),
                'expires' => time() + SecurityKeys::EXPIRATION_INTERVAL,
                'revoked' => null
            ])
            ->create();
    }
}
