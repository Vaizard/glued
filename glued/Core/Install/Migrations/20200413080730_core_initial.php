<?php

use Phinx\Db\Adapter\MysqlAdapter;

class CoreInitial extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_core_authn', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Access credentials to users defined in t_core_users. Each user is allowed a secondary name/pass combination (plausible deniability)',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => true,
                'comment' => 'Object attributes (status: enabled/disabled, allowed IPs, scope - i.e. allwed/forbidden actions, rate limits, etc.)',
            ])
            ->addColumn('c_hash', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'ascii_bin',
                'encoding' => 'ascii',
                'comment' => 'Password or api key hash (according to c_type)',
                'after' => 'c_attr',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'c_hash',
            ])
            ->addColumn('c_ts_modified', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_type', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_TINY,
                'comment' => '0 password, 1 api key',
                'after' => 'c_ts_modified',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row/object id',
                'after' => 'c_type',
            ])
            ->addColumn('c_user_uid', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Corresponds to t_core_users.c_uid',
                'after' => 'c_uid',
            ])
            ->addIndex(['c_uid'], [
                'name' => 'c_uid',
                'unique' => true,
            ])
            ->addIndex(['c_user_uid'], [
                'name' => 'c_user_id',
                'unique' => false,
            ])
            ->addForeignKey('c_user_uid', 't_core_users', 'c_uid', [
                'constraint' => 't_core_authn_ibfk_1',
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
            ])
            ->create();
        $this->table('t_core_domains', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Domains (as in RBAC domains)',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_name', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Domain name',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_name',
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
                'comment' => 'Creator of the domain',
                'after' => 'c_uid',
            ])
            ->addIndex(['c_user_id'], [
                'name' => 'c_user_id',
                'unique' => false,
            ])
            ->addForeignKey('c_user_id', 't_core_users', 'c_uid', [
                'constraint' => 't_core_domains_ibfk_1',
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
            ])
            ->create();
        $this->table('t_core_processlog', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Mail accounts',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_domain_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
            ])
            ->addColumn('c_name', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'User friendly process name',
                'after' => 'c_domain_id',
            ])
            ->addColumn('c_params', 'json', [
                'null' => false,
                'comment' => 'Process start parameters',
                'after' => 'c_name',
            ])
            ->addColumn('c_parent_collection', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Process parent table name',
                'after' => 'c_params',
            ])
            ->addColumn('c_parent_method', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Process parent method',
                'after' => 'c_parent_collection',
            ])
            ->addColumn('c_parent_object', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Process parent uid',
                'after' => 'c_parent_method',
            ])
            ->addColumn('c_result_code', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Process result code',
                'after' => 'c_parent_object',
            ])
            ->addColumn('c_result_message', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Process result message',
                'after' => 'c_result_code',
            ])
            ->addColumn('c_result_payload', 'text', [
                'null' => false,
                'limit' => 65535,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Process result payload',
                'after' => 'c_result_message',
            ])
            ->addColumn('c_result_success', 'bit', [
                'null' => true,
                'limit' => 1,
                'comment' => 'NULL = unknown (still running), 1 = success, 0 = failure',
                'after' => 'c_result_payload',
            ])
            ->addColumn('c_runtime_limit', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'In minutes (0 = unknown/unlimited, 30 = 30 minutes)',
                'after' => 'c_result_success',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_runtime_limit',
            ])
            ->addColumn('c_ts_finished', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp finished',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_uid', 'biginteger', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'identity' => 'enable',
                'comment' => 'Unique row id',
                'after' => 'c_ts_finished',
            ])
            ->addColumn('c_user_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Creator of the worklog item (account)',
                'after' => 'c_uid',
            ])
            ->addIndex(['c_domain_id'], [
                'name' => 'c_domain_id',
                'unique' => false,
            ])
            ->addIndex(['c_user_id'], [
                'name' => 'c_user_id',
                'unique' => false,
            ])
            ->create();
        $this->table('t_core_profiles', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Users\' system profiles (personal, invoicing, etc.)',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Profile data',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row id',
                'after' => 'c_json',
            ])
            ->addColumn('c_users_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Profile owner',
                'after' => 'c_uid',
            ])
            ->create();
        $this->table('t_core_users', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Users\' table, their profile and their attributes',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => true,
                'comment' => 'User\'s account attributes (enabled/disabled, GDPR anonymised, etc.)',
            ])
            ->addColumn('c_email', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Primary email',
                'after' => 'c_attr',
            ])
            ->addColumn('c_lang', 'char', [
                'null' => true,
                'default' => 'en_US',
                'limit' => 5,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Preferred language',
                'after' => 'c_email',
            ])
            ->addColumn('c_name', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'User\'s visible screen name (nickname)',
                'after' => 'c_lang',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp: account created',
                'after' => 'c_name',
            ])
            ->addColumn('c_ts_modified', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp: account modified',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row/object id',
                'after' => 'c_ts_modified',
            ])
            ->addIndex(['c_email'], [
                'name' => 'c_email',
                'unique' => true,
            ])
            ->addIndex(['c_uid'], [
                'name' => 'c_uid',
                'unique' => true,
            ])
            ->addIndex(['c_name'], [
                'name' => 'c_screenname',
                'unique' => false,
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
