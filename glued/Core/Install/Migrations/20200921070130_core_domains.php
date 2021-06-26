<?php

use Phinx\Db\Adapter\MysqlAdapter;

class CoreDomains extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $t = $this->table('t_core_domains', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Domains (as in RBAC domains)',
                'row_format' => 'DYNAMIC',
            ]);
       if (!$t->hasColumn('c_json')) {
            $t->addColumn('c_json', 'json', [
                'null' => true,
                'comment' => 'Domain json',
                'after' => 'c_user_id',
            ])
            ->save();
        }
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
