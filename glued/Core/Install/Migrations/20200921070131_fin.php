<?php

use Phinx\Db\Adapter\MysqlAdapter;

class Fin extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_fin_accounts', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Financial transactions',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row/object id',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp: account created / seen first',
                'after' => 'c_uid',
            ])
            ->addColumn('c_ts_modified', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp: account modified',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_user_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Creator of the account',
                'after' => 'c_ts_modified',
            ])
            ->addColumn('c_domain_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Domain id',
                'after' => 'c_user_id',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Account data (json document)',
                'after' => 'c_domain_id',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => false,
                'comment' => 'Account attributes',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_synced', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp: account transactions last synced',
                'after' => 'c_attr',
            ])
            ->addIndex(['c_domain_id'], [
                'name' => 'c_domain_id',
                'unique' => false,
            ])
            ->addIndex(['c_user_id'], [
                'name' => 'c_user_id',
                'unique' => false,
            ])
            ->addForeignKey('c_user_id', 't_core_users', 'c_uid', [
                'constraint' => 't_fin_accounts_ibfk_1',
                'update' => 'RESTRICT',
                'delete' => 'CASCADE',
            ])
            ->addForeignKey('c_domain_id', 't_core_domains', 'c_uid', [
                'constraint' => 't_fin_accounts_ibfk_2',
                'update' => 'RESTRICT',
                'delete' => 'CASCADE',
            ])
            ->create();
        $this->table('t_fin_trx', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Financial transactions',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_uid', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'identity' => 'enable',
                'comment' => 'Unique row/object id',
            ])
            ->addColumn('c_account_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Account relevant to the transaction (t_fin_accounts.uid)',
                'after' => 'c_uid',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp: transaction created / seen first',
                'after' => 'c_account_id',
            ])
            ->addColumn('c_ts_modified', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp: transaction modified',
                'after' => 'c_ts_created',
            ])
            ->addColumn('c_user_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Creator of the transaction (used for cash transactions inserted manually)',
                'after' => 'c_ts_modified',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Transaction data (json document)',
                'after' => 'c_user_id',
            ])
            ->addColumn('c_ext_order_id', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_json',
            ])
            ->addColumn('c_ext_trx_id', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_ext_order_id',
            ])
            ->addColumn('c_order_int_id', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_ext_trx_id',
            ])
            ->addColumn('c_order_created_by_name', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_order_int_id',
            ])
            ->addColumn('c_order_created_by_uid', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'c_order_created_by_name',
            ])
            ->addColumn('c_order_created_dt', 'char', [
                'null' => true,
                'limit' => 25,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_order_created_by_uid',
            ])
            ->addColumn('c_order_authed_by_name', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_order_created_dt',
            ])
            ->addColumn('c_order_authed_by_uid', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'c_order_authed_by_name',
            ])
            ->addColumn('c_order_authed_dt', 'char', [
                'null' => true,
                'limit' => 25,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_order_authed_by_uid',
            ])
            ->addColumn('c_trx_dt', 'char', [
                'null' => true,
                'limit' => 25,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_order_authed_dt',
            ])
            ->addColumn('c_trx_currency', 'char', [
                'null' => true,
                'limit' => 3,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_trx_dt',
            ])
            ->addColumn('c_trx_volume', 'double', [
                'null' => true,
                'after' => 'c_trx_currency',
            ])
            ->addColumn('c_trx_offset_name', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_trx_volume',
            ])
            ->addColumn('c_trx_offset_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'c_trx_offset_name',
            ])
            ->addColumn('c_trx_offset_bank_code', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_trx_offset_id',
            ])
            ->addColumn('c_trx_offset_bank_bic', 'string', [
                'null' => true,
                'limit' => 11,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_trx_offset_bank_code',
            ])
            ->addColumn('c_trx_offset_iban', 'string', [
                'null' => true,
                'limit' => 34,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_trx_offset_bank_bic',
            ])
            ->addColumn('c_trx_offset_account_nr', 'string', [
                'null' => true,
                'limit' => 34,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_trx_offset_iban',
            ])
            ->addColumn('c_trx_flag_electronic', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_trx_offset_account_nr',
            ])
            ->addColumn('c_trx_flag_cash', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_trx_flag_electronic',
            ])
            ->addColumn('c_trx_flag_card', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_trx_flag_cash',
            ])
            ->addColumn('c_trx_flag_fx', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'c_trx_flag_card',
            ])
            ->addColumn('c_trx_ref_variable', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_trx_flag_fx',
            ])
            ->addColumn('c_trx_ref_specific', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_trx_ref_variable',
            ])
            ->addColumn('c_trx_ref_internal', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_trx_ref_specific',
            ])
            ->addColumn('c_trx_ref_constant', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_trx_ref_internal',
            ])
            ->addColumn('c_trx_intl_volume', 'double', [
                'null' => true,
                'after' => 'c_trx_ref_constant',
            ])
            ->addColumn('c_trx_intl_currency', 'string', [
                'null' => true,
                'limit' => 3,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'after' => 'c_trx_intl_volume',
            ])
            ->addIndex(['c_user_id'], [
                'name' => 'c_user_id',
                'unique' => false,
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
