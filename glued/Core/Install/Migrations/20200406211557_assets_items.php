<?php

use Phinx\Db\Adapter\MysqlAdapter;

class AssetsItems extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_assets_items', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'Assets items database',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row/object id',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'after' => 'c_uid',
                'comment' => 'Assets item data',
            ])
            ->addColumn('c_stor_name', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_data',
            ])
            ->create();
        $this->execute("ALTER TABLE t_assets_items DROP c_stor_name; ALTER TABLE t_assets_items ADD c_stor_name VARCHAR(255) GENERATED ALWAYS AS (json_unquote(json_extract(c_json,'$.name'))) VIRTUAL NOT NULL AFTER c_json;");
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
