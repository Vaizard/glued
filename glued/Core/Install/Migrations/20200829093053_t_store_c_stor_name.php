<?php

use Phinx\Db\Adapter\MysqlAdapter;

class TStoreCStorName extends Phinx\Migration\AbstractMigration
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
            ->addColumn('c_stor_name', 'string', [
                'null' => false,
                'default' => '',
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Name for the stor CAS',
                'after' => 'c_uid',
            ])
            ->save();
        $this->table('t_store_sellers', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Sellers are entities who sell stuff at specified conditions (think aliexpress or amazon sellers)',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_stor_name', 'string', [
                'null' => false,
                'default' => '',
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Name for the stor CAS',
                'after' => 'c_user_id',
            ])
            ->save();
        $this->table('t_store_subscriptions', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Subscriptions are recurring purchase items (donations, memberships, internet subscriptions, periodicals delivery, etc.)',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_stor_name', 'string', [
                'null' => false,
                'default' => '',
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Name for the stor CAS',
                'after' => 'c_uid',
            ])
            ->save();
        $this->table('t_store_tickets', [
                'id' => false,
                'primary_key' => ['c_uid'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_0900_ai_ci',
                'comment' => 'Tickets are time-limited booking and item offerings (tickets to conferences, workshops, merch, etc.)',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('c_stor_name', 'string', [
                'null' => false,
                'default' => '',
                'limit' => 255,
                'collation' => 'utf8mb4_0900_ai_ci',
                'encoding' => 'utf8mb4',
                'comment' => 'Name for the stor CAS',
                'after' => 'c_uid',
            ])
            ->save();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}
