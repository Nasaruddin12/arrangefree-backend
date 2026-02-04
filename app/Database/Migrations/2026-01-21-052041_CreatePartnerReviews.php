<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerReviews extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'auto_increment' => true, 'constraint' => 11, 'unsigned' => true],
            'booking_service_id' => ['type' => 'INT', 'null' => false, 'constraint' => 11, 'unsigned' => true],
            'partner_id'        => ['type' => 'INT', 'null' => false, 'constraint' => 11, 'unsigned' => true],
            'rating'            => ['type' => 'INT', 'constraint' => 1, 'null' => false], // 1 to 5
            'review'            => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('booking_service_id');
        $this->forge->addKey('partner_id');
        $this->forge->createTable('partner_reviews');
    }

    public function down()
    {
        $this->forge->dropTable('partner_reviews');
    }
}
