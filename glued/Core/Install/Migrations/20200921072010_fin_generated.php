<?php

use Phinx\Db\Adapter\MysqlAdapter;

class FinGenerated extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->execute("
            ALTER TABLE t_fin_trx DROP c_ext_order_id;
            ALTER TABLE t_fin_trx ADD c_ext_order_id VARCHAR(255) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.ext.order_id',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_ext_trx_id;
            ALTER TABLE t_fin_trx ADD c_ext_trx_id VARCHAR(255) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.ext.trx_id',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_order_int_id;
            ALTER TABLE t_fin_trx ADD c_order_int_id VARCHAR(255) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.order.uuid',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_order_created_by_name;
            ALTER TABLE t_fin_trx ADD c_order_created_by_name VARCHAR(255) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.order.created_by_name',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_order_created_by_uid;
            ALTER TABLE t_fin_trx ADD c_order_created_by_uid INT GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.order.created_by_uid',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_order_created_dt;
            ALTER TABLE t_fin_trx ADD c_order_created_dt CHAR(25) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.order.created_dt',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_order_authed_by_name;
            ALTER TABLE t_fin_trx ADD c_order_authed_by_name VARCHAR(255) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.order.authed_by_name',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_order_authed_by_uid;
            ALTER TABLE t_fin_trx ADD c_order_authed_by_uid INT GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.order.authed_by_uid',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_order_authed_dt;
            ALTER TABLE t_fin_trx ADD c_order_authed_dt CHAR(25) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.order.authed_dt',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_dt;
            ALTER TABLE t_fin_trx ADD c_trx_dt CHAR(25) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.dt',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_currency;
            ALTER TABLE t_fin_trx ADD c_trx_currency CHAR(3) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.currency',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_volume;
            ALTER TABLE t_fin_trx ADD c_trx_volume DOUBLE GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.volume',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_offset_name;
            ALTER TABLE t_fin_trx ADD c_trx_offset_name VARCHAR(255) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.offset.name',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_offset_id;
            ALTER TABLE t_fin_trx ADD c_trx_offset_id INT GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.offset.id',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_offset_bank_code;
            ALTER TABLE t_fin_trx ADD c_trx_offset_bank_code VARCHAR(255) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.offset.bank_code',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_offset_bank_bic;
            ALTER TABLE t_fin_trx ADD c_trx_offset_bank_bic VARCHAR(11) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.offset.bank_bic',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_offset_iban;
            ALTER TABLE t_fin_trx ADD c_trx_offset_iban VARCHAR(34) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.offset.account_iban',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_offset_account_nr;
            ALTER TABLE t_fin_trx ADD c_trx_offset_account_nr VARCHAR(34) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.offset.account_nr',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_flag_electronic;
            ALTER TABLE t_fin_trx ADD c_trx_flag_electronic TINYINT GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.type.electronic',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_flag_cash;
            ALTER TABLE t_fin_trx ADD c_trx_flag_cash TINYINT GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.type.cash',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_flag_card;
            ALTER TABLE t_fin_trx ADD c_trx_flag_card TINYINT GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.type.card',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_flag_fx;
            ALTER TABLE t_fin_trx ADD c_trx_flag_fx TINYINT GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.type.fx',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_ref_variable;
            ALTER TABLE t_fin_trx ADD c_trx_ref_variable VARCHAR(255) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.ref.variable',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_ref_specific;
            ALTER TABLE t_fin_trx ADD c_trx_ref_specific VARCHAR(255) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.ref.specific',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_ref_internal;
            ALTER TABLE t_fin_trx ADD c_trx_ref_internal VARCHAR(255) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.ref.internal',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_ref_constant;
            ALTER TABLE t_fin_trx ADD c_trx_ref_constant VARCHAR(255) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.ref.constant',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_intl_volume;
            ALTER TABLE t_fin_trx ADD c_trx_intl_volume DOUBLE GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.intl.volume',\"\")) NULL;
            ALTER TABLE t_fin_trx DROP c_trx_intl_currency;
            ALTER TABLE t_fin_trx ADD c_trx_intl_currency VARCHAR(3) GENERATED ALWAYS AS (NULLIF(`c_json`->>'$.intl.currency',\"\")) NULL; 
        ");
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
