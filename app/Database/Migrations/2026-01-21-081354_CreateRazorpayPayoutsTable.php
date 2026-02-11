<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRazorpayPayoutsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'partner_payout_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'razorpay_order_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],

            'razorpay_fund_account_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],

            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'INR',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'created',
                    'processing',
                    'processed',
                    'failed'
                ],
                'default' => 'created',
            ],

            'failure_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'gateway_response' => [
                'type' => 'JSON',
                'null' => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);

        $this->forge->addForeignKey(
            'partner_payout_id',
            'partner_payouts',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('razorpay_payouts', true);
    }

    public function down()
    {
        $this->forge->dropTable('razorpay_payouts', true);
    }
}
