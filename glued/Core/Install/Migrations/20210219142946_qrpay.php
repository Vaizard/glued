<?php

use Phinx\Db\Adapter\MysqlAdapter;

class Qrpay extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_store_payments', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Expected payments table.',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'identity' => 'enable',
                'comment' => 'Unique row id',
            ])
            ->addColumn('c_type', 'string', [
                'null' => false,
                'default' => 'qr',
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'comment' => 'Payment type',
                'after' => 'c_uid',
            ])
            ->addColumn('c_eid', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'comment' => 'Externally generated identification string',
                'after' => 'c_type',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Payment data (json document)',
                'after' => 'c_eid',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp updated',
                'after' => 'c_ts_created',
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
