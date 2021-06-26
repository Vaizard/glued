<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Seed\AbstractSeed;

class CoreDomainsSeeder extends AbstractSeed
{
    public function run()
    {
        $data = [
            [
                'c_name'    => 'all-users',
                'c_user_id' => '1',
            ]
        ];

        $this->table('t_core_domains')->insert($data)->save();
    }
}
