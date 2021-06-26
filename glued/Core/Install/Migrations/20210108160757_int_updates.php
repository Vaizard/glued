<?php

use Phinx\Db\Adapter\MysqlAdapter;

class IntUpdates extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('t_int_cache', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Integrations storage',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'biginteger', [
                'null' => false,
                'signed' => false,
                'identity' => 'enable',
                'comment' => 'Unique row id',
            ])
            ->addColumn('c_object_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'comment' => 'Integrations object id',
                'after' => 'c_uid',
            ])
            ->addColumn('c_fuid', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'comment' => 'Unique foreign sub-identifier (i.e. row number, row data hash, etc.)',
                'after' => 'c_object_id',
            ])
            ->addColumn('c_rev', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'comment' => 'Revision number (default 0).',
                'after' => 'c_fuid',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Cached data (json document)',
                'after' => 'c_rev',
            ])
            ->addColumn('c_hash', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'comment' => 'Hash of c_json',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_hash',
            ])
            ->create();
        $this->table('t_int_objects', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Integration objects',
                'row_format' => 'DYNAMIC',
            ])
            ->changeColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'identity' => 'enable',
                'comment' => 'Unique row id',
            ])
            ->update();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
