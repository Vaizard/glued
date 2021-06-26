<?php

use Phinx\Db\Adapter\MysqlAdapter;

class CovidInitial extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_covid_zakladace', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
            ])
            ->addColumn('c_ts', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'c_uid',
            ])
            ->addColumn('c_name', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_ts',
            ])
            ->addColumn('c_phone', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_name',
            ])
            ->addColumn('c_email', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_phone',
            ])
            ->addColumn('c_notes', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_email',
            ])
            ->addColumn('c_gdpr_yes', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_notes',
            ])
            ->addColumn('c_address', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_gdpr_yes',
            ])
            ->addColumn('c_addr_street', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_address',
            ])
            ->addColumn('c_addr_city', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_addr_street',
            ])
            ->addColumn('c_addr_zip', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_addr_city',
            ])
            ->addColumn('c_addr_note', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_addr_zip',
            ])
            ->addColumn('c_handovered', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_addr_note',
            ])
            ->addColumn('c_delivered', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_handovered',
            ])
            ->addColumn('c_noneed', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_delivered',
            ])
            ->addColumn('c_amount', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'c_noneed',
            ])
            ->addColumn('c_email_sent', 'integer', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'c_amount',
            ])
            ->addColumn('c_email_result', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'c_email_sent',
            ])
            ->addColumn('c_email_body', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_email_result',
            ])
            ->addColumn('c_bad_data', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_email_body',
            ])
            ->addColumn('c_row_hash', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'c_bad_data',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'c_row_hash',
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
