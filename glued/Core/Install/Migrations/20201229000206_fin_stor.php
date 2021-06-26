<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Util\Literal;

class FinStor extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_fin_costs', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Financial transactions',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row/object id',
            ])
            ->addColumn('c_domain_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Domain id',
                'after' => 'c_uid',
            ])
            ->addColumn('c_user_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Creator id',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Account data (json document)',
                'after' => 'c_user_id',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp: account created / seen first',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_modified', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp: account modified',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_stor_name', Literal::from("varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`c_json`,_utf8mb4'$.offset'))) VIRTUAL"), [
                'null' => false,
                'comment' => 'Stor name',
            ])
            ->addIndex(['c_user_id'], [
                'name' => 'c_user_id',
                'unique' => false,
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
