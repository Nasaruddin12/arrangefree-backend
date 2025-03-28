<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingExpenses extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'booking_id'     => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true, // Ensure it's UNSIGNED
            ],
            'amount'         => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'category'       => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'payment_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'transaction_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'vendor_or_client' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'description'    => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at'     => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'updated_at'     => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);

        // Add primary key
        $this->forge->addPrimaryKey('id');

        // Add foreign key (Ensure `bookings.id` is also UNSIGNED in `bookings` table)
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');

        // Create table
        $this->forge->createTable('booking_expenses', true);
    }

    public function down()
    {
        $this->forge->dropTable('booking_expenses', true);
    }
}
