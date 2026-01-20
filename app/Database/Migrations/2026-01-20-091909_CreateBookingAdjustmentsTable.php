<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingAdjustmentsTable extends Migration
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

            'adjustment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'discount, extra_charge, refund, penalty, compensation, waiver, promo, rounding etc',
            ],

            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Reason shown to customer',
            ],

            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'is_addition' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => '1 = add, 0 = subtract',
            ],

            'is_taxable' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Whether GST applies',
            ],

            'cgst_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],

            'sgst_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],

            'created_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'system'],
                'default'    => 'system',
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('booking_id');

        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('booking_adjustments');
    }

    public function down()
    {
        $this->forge->dropTable('booking_adjustments');
    }
}
