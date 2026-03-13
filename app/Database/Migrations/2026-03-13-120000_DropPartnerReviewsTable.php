<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropPartnerReviewsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('partner_reviews')) {
            $this->forge->dropTable('partner_reviews');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('partner_reviews')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'booking_service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'partner_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'rating' => [
                'type'       => 'INT',
                'constraint' => 1,
            ],
            'review' => [
                'type' => 'TEXT',
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
        $this->forge->addKey('booking_service_id');
        $this->forge->addKey('partner_id');
        $this->forge->createTable('partner_reviews');
    }
}
