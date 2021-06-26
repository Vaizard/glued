<?php

use Phinx\Db\Adapter\MysqlAdapter;

class StoreInitial extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->table('t_store_items', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Store items are just regular store items (products and one-time services for purchase)',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => false,
                'comment' => 'Item attributes',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Item data (json document)',
                'after' => 'c_attr',
            ])
            ->addColumn('c_seller_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Seller of this particular item',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_seller_id',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => true,
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
            ->addIndex(['c_seller_id'], [
                'name' => 'c_seller_id',
                'unique' => false,
            ])
            ->addForeignKey('c_seller_id', 't_store_sellers', 'c_uid', [
                'constraint' => 't_store_items_ibfk_1',
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
            ])
            ->create();
        $this->table('t_store_sellers', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Sellers are entities who sell stuff at specified conditions (think aliexpress or amazon sellers)',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => false,
                'comment' => 'Seller attributes',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Seller data (json document)',
                'after' => 'c_attr',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => true,
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
                'comment' => 'Creator of the seller (account)',
                'after' => 'c_uid',
            ])
            ->addIndex(['c_user_id'], [
                'name' => 'c_user_id',
                'unique' => false,
            ])
            ->create();
        $this->table('t_store_subscriptions', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Subscriptions are recurring purchase items (donations, memberships, internet subscriptions, periodicals delivery, etc.)',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => false,
                'comment' => 'Subscription attributes',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Subscription data (json document)',
                'after' => 'c_attr',
            ])
            ->addColumn('c_seller_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Seller of this particular subscription',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_seller_id',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => true,
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
            ->addIndex(['c_seller_id'], [
                'name' => 'c_seller_id',
                'unique' => false,
            ])
            ->addForeignKey('c_seller_id', 't_store_sellers', 'c_uid', [
                'constraint' => 't_store_subscriptions_ibfk_1',
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
            ])
            ->create();
        $this->table('t_store_tickets', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Tickets are time-limited booking and item offerings (tickets to conferences, workshops, merch, etc.)',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_attr', 'json', [
                'null' => false,
                'comment' => 'Ticket attributes',
            ])
            ->addColumn('c_json', 'json', [
                'null' => false,
                'comment' => 'Ticket data (json document)',
                'after' => 'c_attr',
            ])
            ->addColumn('c_seller_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'comment' => 'Ticket seller id',
                'after' => 'c_json',
            ])
            ->addColumn('c_ts_created', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Timestamp created',
                'after' => 'c_seller_id',
            ])
            ->addColumn('c_ts_updated', 'timestamp', [
                'null' => true,
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
            ->addIndex(['c_seller_id'], [
                'name' => 'c_seller_id',
                'unique' => false,
            ])
            ->addForeignKey('c_seller_id', 't_store_sellers', 'c_uid', [
                'constraint' => 't_store_tickets_ibfk_1',
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
