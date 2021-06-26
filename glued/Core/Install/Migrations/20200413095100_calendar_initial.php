<?php

use Phinx\Db\Adapter\MysqlAdapter;

class CalendarInitial extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_calendar_sources', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Uris to ICAL calendars and friends.',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Calendar data (json document)',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => false,
                'comment' => 'Calendar attributes/state (json document)',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_attr',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
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
                'comment' => 'Creator of the calendar (account)',
                'after' => 'c_uid',
            ])
            ->addColumn('c_domain_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Domain id',
                'after' => 'c_user_id',
            ])
            ->addColumn('c_flag_deleted', 'boolean', [
                'null' => false,
                'limit' => MysqlAdapter::INT_TINY,
                'comment' => 'Virtual column c_attr->>deleted',
                'after' => 'c_domain_id',
            ])
            ->addIndex(['c_user_id'], [
                'name' => 'c_user_id',
                'unique' => false,
            ])
            ->addIndex(['c_domain_id'], [
                'name' => 'c_domain_id',
                'unique' => false,
            ])
            ->addForeignKey('c_user_id', 't_core_users', 'c_uid', [
                'constraint' => 't_calendar_sources_ibfk_1',
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
            ])
            ->addForeignKey('c_domain_id', 't_core_domains', 'c_uid', [
                'constraint' => 't_calendar_sources_ibfk_3',
                'update' => 'RESTRICT',
                'delete' => 'CASCADE',
            ])
            ->create();
        $this->execute("ALTER TABLE `t_calendar_sources` DROP `c_flag_deleted`; ALTER TABLE `t_calendar_sources` ADD `c_flag_deleted` boolean GENERATED ALWAYS AS (IF((json_unquote(json_extract(`c_attr`,'$.deleted'))) = 'true', 1, 0)) VIRTUAL NOT NULL;");
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
