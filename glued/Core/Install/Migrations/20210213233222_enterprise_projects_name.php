<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Util\Literal;

class EnterpriseProjectsName extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_enterprise_projects', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Projects in enterprise module.',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_name', Literal::from("varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (`c_json`->>'$.name') STORED"), [
                'null' => true,
                'comment' => '(Generated) Contains project name.',
            ])
            ->save();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
