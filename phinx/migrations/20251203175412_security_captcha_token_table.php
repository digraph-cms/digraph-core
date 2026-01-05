<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SecurityCaptchaTokenTable extends AbstractMigration
{

    public function change(): void
    {
        $this->table('security_captcha_token')
            ->addColumn('token', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('expires', 'integer', ['null' => false, 'signed' => false])
            ->create();
    }

}
