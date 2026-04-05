<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRazorpayStatusToBookingPayments extends Migration
{
    public function up()
    {
        $fields = [
            'razorpay_status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'status',
            ],
        ];

        $this->forge->addColumn('booking_payments', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('booking_payments', 'razorpay_status');
    }
}
