<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GeneratedColumns extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->execute("ALTER TABLE `t_core_domains` DROP `c_stor_name`; ALTER TABLE `t_core_domains` ADD `c_stor_name` varchar(255) GENERATED ALWAYS AS (`c_name`);");
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
