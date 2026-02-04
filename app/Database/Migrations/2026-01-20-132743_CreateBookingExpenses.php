<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingExpenses extends Migration
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

            'booking_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'expense_type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'partner_payout',
                    'material',
                    'transport',
                    'vendor',
                    'misc'
                ],
            ],

            'expense_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'comment'    => 'Short description',
            ],

            'expense_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'payment_mode' => [
                'type'       => 'ENUM',
                'constraint' => ['cash', 'upi', 'bank_transfer'],
            ],

            'reference_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'UPI txn / bank ref / voucher no',
            ],

            'paid_to' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'comment'    => 'Partner / vendor name',
            ],

            'paid_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'paid', 'cancelled'],
                'default'    => 'pending',
            ],

            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'created_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'system'],
                'default'    => 'admin',
            ],

            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        // KEYS
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('booking_id');
        $this->forge->addKey('expense_type');
        $this->forge->addKey('status');

        // FOREIGN KEYS (SAFE FOR FINANCE)
        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('booking_expenses');
    }

    public function down()
    {
        $this->forge->dropTable('booking_expenses', true);
    }
}
