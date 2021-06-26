<?php

use Phinx\Db\Adapter\MysqlAdapter;

class MailInitial extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_mail_accounts', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Mail accounts',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_data', 'blob', [
                'null' => false,
                'limit' => MysqlAdapter::BLOB_REGULAR,
                'comment' => 'Mail account data',
            ])
            ->addColumn('c_domain_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'c_data',
            ])
            ->addColumn('c_frequency', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Fetch frequecny: 0 = on demand, 30 = every 30 minutes',
                'after' => 'c_domain_id',
            ])
            ->addColumn('c_name', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'User-friendly name',
                'after' => 'c_frequency',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_name',
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
            ->addColumn('c_user_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Creator of the worklog item (account)',
                'after' => 'c_uid',
            ])
            ->addIndex(['c_domain_id'], [
                'name' => 'c_domain_id',
                'unique' => false,
            ])
            ->addIndex(['c_user_id'], [
                'name' => 'c_user_id',
                'unique' => false,
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
