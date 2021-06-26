<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Util\Literal;

class ContactsGenerated extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_contacts_objects', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Contacts items',
                'row_format' => 'DYNAMIC',
            ])
            ->changeColumn('c_vatid', Literal::from("varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (`c_json`->>'$.nat[0].vatid') STORED"), [
                'null' => true,
                'comment' => '(Generated) Contains VATID if available.',
            ])
            ->changeColumn('c_fn', Literal::from("varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (`c_json`->>'$.fn') STORED"), [
                'null' => true,
                'comment' => '(Generated) Full name.',
            ])
            ->changeColumn('c_kind_legal', Literal::from("tinyint GENERATED ALWAYS AS (`c_json`->>'$.kind.l') STORED"), [
                'null' => true,
                'comment' => '(Generated) Flag, true if contact is a legal person.',
            ])
            ->changeColumn('c_kind_natural', Literal::from("tinyint GENERATED ALWAYS AS (`c_json`->>'$.kind.n') STORED"), [
                'null' => true,
                'comment' => '(Generated) Flag, true if contact is a natural person.',
            ])
            ->addColumn('c_regid', Literal::from("varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (`c_json`->>'$.nat[0].regid') STORED"), [
                'null' => true,
                'comment' => '(Generated) Contains REGID if available.',
                'after' => 'c_vatid',
            ])
            ->addColumn('c_natid', Literal::from("varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (`c_json`->>'$.nat[0].natid') STORED"), [
                'null' => true,
                'comment' => '(Generated) Contains NATID if available.',
                'after' => 'c_vatid',
            ])
            ->update();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
