<?php

use Phinx\Db\Adapter\MysqlAdapter;

class Updt extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('casbin_rule', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
            ])
            ->addColumn('ptype', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('v0', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ptype',
            ])
            ->addColumn('v1', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'v0',
            ])
            ->addColumn('v2', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'v1',
            ])
            ->addColumn('v3', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'v2',
            ])
            ->addColumn('v4', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'v3',
            ])
            ->addColumn('v5', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'v4',
            ])
            ->create();
        $this->table('t_calendar_objects', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Uris to ICAL calendars and friends.',
                'row_format' => 'DYNAMIC',
            ])
            ->changeColumn('c_source_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Calendar source id',
                'after' => 'c_uid',
            ])
            ->addColumn('c_object_sequence', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'c_object_uid',
            ])
            ->addColumn('c_object_recurrence_id', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'c_object_sequence',
            ])
            ->changeColumn('c_object_hash', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'comment' => 'Serialized ical object (test for changes when object is pulled from a remote calendar)',
                'after' => 'c_object_recurrence_id',
            ])
            ->changeColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Preextracted calendar event data (json document)',
                'after' => 'c_object_hash',
            ])
            ->changeColumn('c_attr', 'json', [
                'null' => false,
                'comment' => 'Calendar event attributes/state (json document)',
                'after' => 'c_json',
            ])
            ->changeColumn('c_revision_counter', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Number of revisions to this event',
                'after' => 'c_attr',
            ])
            ->changeColumn('c_revisions', 'json', [
                'null' => false,
                'comment' => 'Json with all revisions.',
                'after' => 'c_revision_counter',
            ])
            ->changeColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_revisions',
            ])
            ->changeColumn('c_ts_updated', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp updated',
                'after' => 'c_ts_created',
            ])
            ->removeColumn('c_object')
            ->addIndex(['c_object_hash'], [
                'name' => 'c_object_hash',
                'unique' => false,
                'limit' => '5',
            ])
            ->addIndex(['c_object_uid'], [
                'name' => 'c_object_uid',
                'unique' => false,
                'limit' => '5',
            ])
            ->save();
        $this->table('t_contacts_objects', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Contacts items',
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
                'after' => 'c_uid',
            ])
            ->addColumn('c_user_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'c_domain_id',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Contacts item data (json document)',
                'after' => 'c_user_id',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => false,
                'comment' => 'Contacts item attributes (json document)',
                'after' => 'c_json',
            ])
            ->addColumn('c_stor_name', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_attr',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_stor_name',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp updated',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_kind_legal', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_ts_updated',
            ])
            ->addColumn('c_kind_natural', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_kind_legal',
            ])
            ->addColumn('c_fn', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_kind_natural',
            ])
            ->addColumn('c_vatid', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_fn',
            ])
            ->addIndex(['c_domain_id'], [
                'name' => 'c_domain_id',
                'unique' => false,
            ])
            ->addForeignKey('c_domain_id', 't_core_domains', 'c_uid', [
                'constraint' => 't_contacts_objects_ibfk_1',
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
            ])
            ->create();
        $this->table('t_contacts_rels', [
                'id' => false,
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Stores relationships between contact items using two rows for one relationship (i.e. 1 is parent of 2, 2 has parent 1).',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid1', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'First t_contacts_items.uid',
            ])
            ->addColumn('c_uid2', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Second t_contacts_items.uid',
                'after' => 'c_uid1',
            ])
            ->addColumn('c_type', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'c_uid2',
            ])
            ->addColumn('c_label', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_type',
            ])
            ->addColumn('c_dt_from', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'c_label',
            ])
            ->addColumn('c_dt_till', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'c_dt_from',
            ])
            ->addIndex(['c_uid1'], [
                'name' => 'contact_id_1',
                'unique' => false,
            ])
            ->addIndex(['c_uid2'], [
                'name' => 'contact_id_2',
                'unique' => false,
            ])
            ->addForeignKey('c_uid1', 't_contacts_objects', 'c_uid', [
                'constraint' => 't_contacts_rels_ibfk_2',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->addForeignKey('c_uid2', 't_contacts_objects', 'c_uid', [
                'constraint' => 't_contacts_rels_ibfk_4',
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->create();
        $this->table('t_enterprise_projects', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'projects in enterprise module',
                'row_format' => 'DYNAMIC',
            ])
            ->changeColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row id',
            ])
            ->changeColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Item data (json document)',
                'after' => 'c_uid',
            ])
            ->changeColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_json',
            ])
            ->changeColumn('c_ts_updated', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp updated',
                'after' => 'c_ts_created',
            ])
            ->save();
        $this->table('t_enterprise_projects_rels', [
                'id' => false,
                'primary_key' => ['c_parent', 'c_child'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_parent', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
            ])
            ->addColumn('c_child', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'c_parent',
            ])
            ->addIndex(['c_child'], [
                'name' => 'c_child',
                'unique' => false,
            ])
            ->create();
        $this->table('t_fin_accounts', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Financial transactions',
                'row_format' => 'DYNAMIC',
            ])
            ->changeColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row/object id',
            ])
            ->save();
        $this->table('t_fin_trx', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Financial transactions',
                'row_format' => 'DYNAMIC',
            ])
            ->changeColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row/object id',
            ])
            ->changeColumn('c_account_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Account relevant to the transaction (t_fin_accounts.uid)',
                'after' => 'c_uid',
            ])
            ->save();
        $this->table('t_store_sellers', [
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
                'comment' => 'Domain id',
                'after' => 'c_user_id',
            ])
            ->save();
        $this->table('t_contacts_items')->drop()->save();
        $this->table('t_enterprise_projects_tree')->drop()->save();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
