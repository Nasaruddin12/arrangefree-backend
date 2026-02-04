<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerJobItemMedia extends Migration
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

            'partner_job_item_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'media_type' => [
                'type'       => 'ENUM',
                'constraint' => ['image', 'video'],
            ],

            'media_path' => [
                'type' => 'TEXT',
                'comment' => 'Stored file path or URL',
            ],

            'uploaded_by' => [
                'type'       => 'ENUM',
                'constraint' => ['partner', 'admin'],
            ],

            'uploaded_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);

        $this->forge->addForeignKey(
            'partner_job_item_id',
            'partner_job_items',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('partner_job_item_media', true);
    }

    public function down()
    {
        $this->forge->dropTable('partner_job_item_media', true);
    }
}
