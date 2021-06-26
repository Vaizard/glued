<?php

use Phinx\Db\Adapter\MysqlAdapter;

class CoreAuthReset extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_core_authn_reset', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Reset access credentials',
                'row_format' => 'DYNAMIC',
            ])
            ->renameColumn('c_user_uid', 'c_user_id')
            ->renameColumn('c_authn_uid', 'c_auth_id')
            ->save();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
