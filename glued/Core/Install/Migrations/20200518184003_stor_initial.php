<?php

use Phinx\Db\Adapter\MysqlAdapter;

class StorInitial extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_stor_links', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Content aware storage links table. All files in t_stor_objects are unique, t_stor_links provides soft/hard links (depending on stor\'s configuration) to appropriate locations with a user-friendly name.',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_filename', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'User firendly file-name of the soft/hardlink.',
            ])
            ->addColumn('c_inherit_object', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Authorization to be inherited according to table:object pair.',
                'after' => 'c_filename',
            ])
            ->addColumn('c_inherit_table', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Authorization to be inherited according to table:object pair.',
                'after' => 'c_inherit_object',
            ])
            ->addColumn('c_sha512', 'char', [
                'null' => false,
                'limit' => 128,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Assigns a link to a specific object (row in t_stor_objects).',
                'after' => 'c_inherit_table',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp (link created).',
                'after' => 'c_sha512',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Link id',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_user_id', 'integer', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'User who created a new link (by uploading a new or existing db object to a specific location under a user-friendly name .. see c_filename)',
                'after' => 'c_uid',
            ])
            ->create();
        $this->table('t_stor_objects', [
                'id' => false,
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Content aware storage objects table. An object is a file with a unique sha512 hash (see the c_sha512 generated from c_json).',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Files metadata.',
            ])
            ->addColumn('c_sha512', 'char', [
                'null' => true,
                'limit' => 128,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'c_json',
            ])
            ->addIndex(['c_sha512'], [
                'name' => 'c_sha512',
                'unique' => false,
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
