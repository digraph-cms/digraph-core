<?php

declare(strict_types=1);

use DigraphCMS\Digraph;
use Phinx\Migration\AbstractMigration;

final class DefaultHomePage extends AbstractMigration
{
    public function change(): void
    {
        $uuid = Digraph::uuid();
        $this->table('page')
            ->insert([
                'uuid' => $uuid,
                'name' => 'Home',
                'class' => 'page',
                'slug_pattern' => '/home',
                'data' => json_encode([
                    "content" => [
                        "body" => [
                            "created" => time(),
                            "created_by" => "system",
                            "source" => implode(PHP_EOL . PHP_EOL, [
                                '# Home page',
                                '[toc/]'
                            ])
                        ]
                    ]
                ]),
                'created' => time(),
                'created_by' => 'system',
                'updated' => time(),
                'updated_by' => 'system'
            ])
            ->save();
        $this->table('page_slug')
            ->insert([
                'url' => 'home',
                'page_uuid' => $uuid
            ])
            ->save();
    }
}
