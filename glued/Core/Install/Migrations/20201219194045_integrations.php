<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Util\Literal;

class Integrations extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_int_objects', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Integration objects',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row id',
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
                'comment' => 'Creator of the integration object',
                'after' => 'c_domain_id',
            ])
            ->addColumn('c_progress', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Progress itinerator',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => false,
                'comment' => 'Integration object attributes (i.e. not authorized, etc.)',
                'after' => 'c_user_id',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Integrations object definition',
                'after' => 'c_attr',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp updated',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_provider', Literal::from("varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`c_json`,_utf8mb4'$.provider'))) VIRTUAL"), [
                'null' => false,
                'comment' => '(Generated) provider',
            ])
            ->addColumn('c_service', Literal::from("varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`c_json`,_utf8mb4'$.service'))) VIRTUAL"), [
                'null' => false,
                'comment' => '(Generated) service',
            ])
            ->addColumn('c_stor_name', Literal::from("varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`c_json`,_utf8mb4'$.name'))) VIRTUAL"), [
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
