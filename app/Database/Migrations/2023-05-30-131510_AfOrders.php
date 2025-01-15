<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfOrders extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'customer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'address_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'total' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'discount' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'subtotal' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'razorpay_initiate_order_id' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'is_null' => true,
            ],
            'razorpay_order_id' => [
                'type' => 'VARCHAR',
                'constraint' => 51
            ],
            'transaction_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'invoice_id' => [
                'type' => 'VARCHAR',
                'constraint' => 51,
            ],
            'is_cod' => [
                'type' => 'SMALLINT',
                'default' => 0,
            ],
            'coupon' => [
                'type' => 'INT',
                'is_null' => true,
            ],
            'status' => [
                'type' => 'SMALLINT',
            ],
            'payment_status' => [
                'type' => 'SMALLINT',
                'default' => 0,
                'constraint' => 6,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_orders');
    }

    public function down()
    {
        $this->forge->dropTable('af_orders');
    }
}
