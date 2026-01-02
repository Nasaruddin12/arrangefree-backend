<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateBookingsCreatorAndUserConfirmation extends Migration
{
    public function up()
    {
        $this->forge->addColumn('bookings', [

            'created_by_type' => [
                'type'       => 'ENUM',
                'constraint' => ['user', 'admin', 'partner', 'system'],
                'default'    => 'user',
                'after'      => 'applied_coupon',
            ],

            'created_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'created_by_type',
            ],

            'created_by_role' => [
                'type'       => 'ENUM',
                'constraint' => ['super_admin', 'admin', 'coordinator'],
                'null'       => true,
                'after'      => 'created_by_id',
            ],

            'user_confirmed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'created_by_role',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('bookings', [
            'created_by_type',
            'created_by_id',
            'created_by_role',
            'user_confirmed_at',
        ]);
    }
}
