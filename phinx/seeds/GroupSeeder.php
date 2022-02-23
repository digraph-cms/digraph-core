<?php


use Phinx\Seed\AbstractSeed;

class GroupSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run()
    {
        $data = [
            [
                'uuid' => 'admins',
                'name' => 'Administrators'
            ],
            [
                'uuid' => 'editors',
                'name' => 'Editors'
            ]
        ];
        $this->table('user_group')
            ->insert($data)
            ->saveData();
    }
}
