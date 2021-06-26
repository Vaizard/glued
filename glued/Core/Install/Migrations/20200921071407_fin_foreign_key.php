<?php

use Phinx\Db\Adapter\MysqlAdapter;

class FinForeignKey extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_fin_trx', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Financial transactions',
                'row_format' => 'DYNAMIC',
            ])
            ->addIndex(['c_account_id'], [
                'name' => 'c_account_id',
                'unique' => false,
            ])
            ->addForeignKey('c_account_id', 't_fin_accounts', 'c_uid', [
                'constraint' => 't_fin_trx_ibfk_1',
                'update' => 'RESTRICT',
                'delete' => 'CASCADE',
            ])
            ->save();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
