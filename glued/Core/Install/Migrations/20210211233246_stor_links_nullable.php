<?php

use Phinx\Db\Adapter\MysqlAdapter;

class StorLinksNullable extends Phinx\Migration\AbstractMigration
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
            ->changeColumn('c_inherit_object', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'comment' => 'Authorization to be inherited according to table:object pair.',
                'after' => 'c_filename',
            ])
            ->changeColumn('c_inherit_table', 'string', [
                'null' => false,
                'limit' => 50,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Authorization to be inherited according to table:object pair.',
                'after' => 'c_inherit_object',
            ])
            ->save();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
