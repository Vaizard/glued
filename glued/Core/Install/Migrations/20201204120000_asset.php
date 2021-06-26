<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Util\Literal;

class Asset extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_assets_items', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Assets items',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_vatid', Literal::from("varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (`c_json`->>'$.nat[0].vatid') VIRTUAL"), [
                'null' => true,
                'comment' => 'Generated, contains VATID if available.',
            ])
            ->update();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
