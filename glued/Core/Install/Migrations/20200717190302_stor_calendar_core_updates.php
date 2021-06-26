<?php

use Phinx\Db\Adapter\MysqlAdapter;

class StorCalendarCoreUpdates extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_calendar_objects', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Uris to ICAL calendars and friends.',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row id',
            ])
            ->addColumn('c_object', 'text', [
                'null' => false,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Serialized ical object',
                'after' => 'c_uid',
            ])
            ->addColumn('c_object_uid', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'comment' => 'ical uid',
                'after' => 'c_object',
            ])
            ->addColumn('c_object_hash', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'comment' => 'Serialized ical object (test for changes when object is pulled from a remote calendar)',
                'after' => 'c_object_uid',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Preextracted calendar event data (json document)',
                'after' => 'c_object_hash',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => false,
                'comment' => 'Calendar event attributes/state (json document)',
                'after' => 'c_json',
            ])
            ->addColumn('c_source_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Calendar source id',
                'after' => 'c_attr',
            ])
            ->addColumn('c_revision_counter', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Number of revisions to this event',
                'after' => 'c_source_id',
            ])
            ->addColumn('c_revisions', 'json', [
                'null' => false,
                'comment' => 'Json with all revisions.',
                'after' => 'c_revision_counter',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_revisions',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp updated',
                'after' => 'c_ts_created',
            ])
            ->addIndex(['c_source_id'], [
                'name' => 'c_source_id',
                'unique' => false,
            ])
            ->create();
        $this->table('t_core_authn_reset', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Reset access credentials',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row/object id',
            ])
            ->addColumn('c_user_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Corresponds to t_core_users.c_uid',
                'after' => 'c_uid',
            ])
            ->addColumn('c_authn_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Corresponds to t_core_authn.c_uid',
                'after' => 'c_user_uid',
            ])
            ->addColumn('c_token', 'char', [
                'null' => false,
                'limit' => 44,
                'collation' => 'ascii_bin',
                'encoding' => 'ascii',
                'comment' => 'Password reset token',
                'after' => 'c_authn_uid',
            ])
            ->addColumn('c_ts_timeout', 'timestamp', [
                'null' => false,
                'after' => 'c_token',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Request created',
                'after' => 'c_ts_timeout',
            ])
            ->addColumn('c_ts_used', 'timestamp', [
                'null' => true,
                'after' => 'c_ts_created',
            ])
            ->addIndex(['c_authn_uid'], [
                'name' => 'c_authn_uid',
                'unique' => false,
            ])
            ->addIndex(['c_token'], [
                'name' => 'c_token',
                'unique' => false,
                'limit' => '4',
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
                'constraint' => 't_core_authn_reset_ibfk_2',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->addForeignKey('c_authn_uid', 't_core_authn', 'c_uid', [
                'constraint' => 't_core_authn_reset_ibfk_4',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->create();
        $this->table('t_core_log', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'biginteger', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'identity' => 'enable',
                'comment' => 'Log row uid',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Log timestamp',
                'after' => 'c_uid',
            ])
            ->addColumn('c_ts_reviewed', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Review timestamp',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_event', 'char', [
                'null' => false,
                'limit' => 48,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'comment' => 'Event and service name',
                'after' => 'c_ts_reviewed',
            ])
            ->addColumn('c_event_hash', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'c_event',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Additional json data',
                'after' => 'c_event_hash',
            ])
            ->addColumn('c_user_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'User ID (t_core_users.uid)',
                'after' => 'c_json',
            ])
            ->addColumn('c_authn_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Authn ID (t_core_authn.uid)',
                'after' => 'c_user_uid',
            ])
            ->addColumn('c_hash_os', 'binary', [
                'null' => false,
                'limit' => 20,
                'comment' => 'Client OS hash',
                'after' => 'c_authn_uid',
            ])
            ->addColumn('c_hash_country', 'binary', [
                'null' => false,
                'limit' => 20,
                'comment' => 'Client country hash',
                'after' => 'c_hash_os',
            ])
            ->addColumn('c_hash_ip', 'binary', [
                'null' => false,
                'limit' => 20,
                'comment' => 'Client IP hash',
                'after' => 'c_hash_country',
            ])
            ->addColumn('c_hash_ua', 'binary', [
                'null' => false,
                'limit' => 20,
                'comment' => 'Client user-agent hash',
                'after' => 'c_hash_ip',
            ])
            ->addColumn('c_hash_headers', 'binary', [
                'null' => false,
                'limit' => 20,
                'comment' => 'Client headers hash',
                'after' => 'c_hash_ua',
            ])
            ->addIndex(['c_authn_uid'], [
                'name' => 'c_authn_uid',
                'unique' => false,
            ])
            ->addIndex(['c_hash_country'], [
                'name' => 'c_hash_country',
                'unique' => false,
                'limit' => '6',
            ])
            ->addIndex(['c_hash_headers'], [
                'name' => 'c_hash_headers',
                'unique' => false,
                'limit' => '6',
            ])
            ->addIndex(['c_hash_ip'], [
                'name' => 'c_hash_ip',
                'unique' => false,
                'limit' => '6',
            ])
            ->addIndex(['c_hash_os'], [
                'name' => 'c_hash_os',
                'unique' => false,
                'limit' => '6',
            ])
            ->addIndex(['c_hash_ua'], [
                'name' => 'c_hash_useragent',
                'unique' => false,
                'limit' => '6',
            ])
            ->addIndex(['c_user_uid'], [
                'name' => 'c_user_uid',
                'unique' => false,
            ])
            ->addForeignKey('c_user_uid', 't_core_users', 'c_uid', [
                'constraint' => 't_core_log_ibfk_1',
                'update' => 'NO_ACTION',
                'delete' => 'NO_ACTION',
            ])
            ->addForeignKey('c_authn_uid', 't_core_authn', 'c_uid', [
                'constraint' => 't_core_log_ibfk_3',
                'update' => 'NO_ACTION',
                'delete' => 'NO_ACTION',
            ])
            ->create();
        $this->table('t_stor_links', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Content aware storage links table. All files in t_stor_objects are unique, t_stor_links provides soft/hard links (depending on stor\'s configuration) to appropriate locations with a user-friendly name.',
                'row_format' => 'DYNAMIC',
            ])
            ->changeColumn('c_filename', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'User firendly file-name of the soft/hardlink.',
            ])
            ->changeColumn('c_inherit_object', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Authorization to be inherited according to table:object pair.',
                'after' => 'c_filename',
            ])
            ->changeColumn('c_inherit_table', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Authorization to be inherited according to table:object pair.',
                'after' => 'c_inherit_object',
            ])
            ->changeColumn('c_sha512', 'char', [
                'null' => false,
                'limit' => 128,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'comment' => 'Assigns a link to a specific object (row in t_stor_objects).',
                'after' => 'c_inherit_table',
            ])
            ->changeColumn('c_ts_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp (link created).',
                'after' => 'c_sha512',
            ])
            ->changeColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Link id',
                'after' => 'c_ts_created',
            ])
            ->changeColumn('c_user_id', 'integer', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'User who created a new link (by uploading a new or existing db object to a specific location under a user-friendly name .. see c_filename)',
                'after' => 'c_uid',
            ])
            ->save();
        $this->execute("ALTER TABLE `t_stor_objects` DROP `c_sha512`; ALTER TABLE `t_stor_objects` ADD `c_sha512` char(128) GENERATED ALWAYS AS (json_unquote(json_extract(`c_json`,'$.data.sha512'))) VIRTUAL NOT NULL;");
        $this->table('t_stor_objects', [
                'id' => false,
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Content aware storage objects table. An object is a file with a unique sha512 hash (see the c_sha512 generated from c_json).',
                'row_format' => 'DYNAMIC',
            ])
            ->addIndex(['c_sha512'], [
                'name' => 'c_sha512',
                'unique' => false,
                'limit' => '6',
            ])
            ->save();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
