<?php

use Phinx\Db\Adapter\MysqlAdapter;

class WorklogInitial extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_worklog_items', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Sellers are entities who sell stuff at specified conditions (think aliexpress or amazon sellers)',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_domain_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Worklog item data (json document)',
                'after' => 'c_domain_id',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp updated',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row id',
                'after' => 'c_ts_updated',
            ])
            ->addColumn('c_user_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Creator of the worklog item (account)',
                'after' => 'c_uid',
            ])
            ->addColumn('c_stor_name', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_json',
            ])
            ->addIndex(['c_domain_id'], [
                'name' => 'c_domain_id',
                'unique' => false,
            ])
            ->addIndex(['c_user_id'], [
                'name' => 'c_user_id',
                'unique' => false,
            ])
            ->addForeignKey('c_user_id', 't_core_users', 'c_uid', [
                'constraint' => 't_worklog_items_ibfk_1',
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
            ])
            ->addForeignKey('c_domain_id', 't_core_domains', 'c_uid', [
                'constraint' => 't_worklog_items_ibfk_2',
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
            ])
            ->create();
        // c_stor_name is used as the virtual directory name for the stor CAS module (specifically for its browsing component). Since the c_json schema/structure of each module each app/module is different, we need to make sure to set up the path individually.
        $this->execute("ALTER TABLE t_worklog_items DROP c_stor_name; ALTER TABLE t_worklog_items ADD c_stor_name VARCHAR(255) GENERATED ALWAYS AS (json_unquote(json_extract(c_json,'$.summary'))) VIRTUAL NOT NULL AFTER c_user_id;");
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
