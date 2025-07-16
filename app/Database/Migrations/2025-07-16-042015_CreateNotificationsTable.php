<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'unsigned' => true],
            'user_type'        => ['type' => 'ENUM("customer","partner","admin")', 'default' => 'customer'], // ✅ NEW
            'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'message'          => ['type' => 'TEXT'],
            'type'             => ['type' => 'VARCHAR', 'constraint' => 100],
            'image'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'navigation_screen'=> ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'navigation_id'    => ['type' => 'INT', 'null' => true],
            'is_read'          => ['type' => 'BOOLEAN', 'default' => false],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('notifications');
    }

    public function down()
    {
        $this->forge->dropTable('notifications');
    }
}
