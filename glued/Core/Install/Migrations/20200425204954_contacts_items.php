<?php

use Phinx\Db\Adapter\MysqlAdapter;

class ContactsItems extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_contacts_items', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Contacts items',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_domain_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Contacts item data (json document)',
                'after' => 'c_domain_id',
            ])
            ->addColumn('c_stor_name', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_json',
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
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row id',
                'after' => 'c_ts_updated',
            ])
            ->addIndex(['c_domain_id'], [
                'name' => 'c_domain_id',
                'unique' => false,
            ])
            ->addForeignKey('c_domain_id', 't_core_domains', 'c_uid', [
                'constraint' => 't_contacts_items_ibfk_1',
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
