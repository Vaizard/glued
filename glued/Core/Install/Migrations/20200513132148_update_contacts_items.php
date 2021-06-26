<?php

use Phinx\Db\Adapter\MysqlAdapter;

class UpdateContactsItems extends Phinx\Migration\AbstractMigration
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
            ->changeColumn('c_domain_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
            ])
            ->changeColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Contacts item data (json document)',
                'after' => 'c_domain_id',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => false,
                'comment' => 'Contacts item attributes (json document)',
                'after' => 'c_json',
            ])
            ->changeColumn('c_stor_name', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_attr',
            ])
            ->changeColumn('c_ts_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_stor_name',
            ])
            ->changeColumn('c_ts_updated', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp updated',
                'after' => 'c_ts_created',
            ])
            ->changeColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row id',
                'after' => 'c_ts_updated',
            ])
            ->save();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
