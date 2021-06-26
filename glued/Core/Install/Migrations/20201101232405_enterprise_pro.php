<?php

use Phinx\Db\Adapter\MysqlAdapter;

class EnterprisePro extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_enterprise_projects', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'projects in enterprise module',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row id',
                'after' => 'c_ts_updated',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Item data (json document)',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp updated',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_stor_name', 'string', [
                'null' => false,
                'default' => '',
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Name for the stor CAS',
                'after' => 'c_uid',
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
