<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfCoupon extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'coupon_category' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'shop_keeper' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'channel_partner' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'area' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'universal' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'coupon_type' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'coupon_type_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'coupon_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'coupon_expiry' => [
                'type' => 'VARCHAR',
                'constraint' => 101,

            ],
            'cart_minimum_amount' => [
                'type' => 'INT',
                'constarint' => '11',
            ],
            'coupon_use_limit' => [
                'type' => 'INT',
                'constraint' => 5,
            ],
            'coupon_used_count' => [
                'type' => 'INT',
            ],
            'coupon_per_user_limit' => [
                'type' => 'INT',
                'constraint' => 5,
            ],
            'coupon_code' => [
                'type' => 'VARCHAR',
                'constraint' => 101,
            ],
            'terms_and_conditions' => [
                'type' => 'TEXT',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('af_coupons');
    }

    public function down()
    {
        $this->forge->dropTable('af_coupons');
    }
}
