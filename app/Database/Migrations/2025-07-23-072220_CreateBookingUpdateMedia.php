<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingUpdateMedia extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'booking_update_id'=> ['type' => 'INT', 'null' => false],
            'media_type'        => ['type' => 'VARCHAR', 'constraint' => '50', 'default' => 'image'], // image, video, doc, etc.
            'file_url'          => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => false],
            'label'             => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true], // optional (before/after)
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('booking_update_media');
    }

    public function down()
    {
        $this->forge->dropTable('booking_update_media');
    }
}
