<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceGalleryTable extends Migration
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
            'service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'media_type' => [
                'type'       => 'ENUM',
                'constraint' => ['image', 'video', 'tutorial'],
                'null'       => false,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'media_url' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'thumbnail_url' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sort_order' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
               'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('service_id');
        $this->forge->addKey('media_type');
        $this->forge->createTable('service_gallery');

        // Add ON UPDATE CURRENT_TIMESTAMP for updated_at
        $this->db->query('ALTER TABLE service_gallery MODIFY updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down()
    {
        $this->forge->dropTable('service_gallery');
    }
}
