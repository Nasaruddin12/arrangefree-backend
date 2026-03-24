<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGoogleReviewsCacheTable extends Migration
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
            'place_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'response_json' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('place_id');
        $this->forge->createTable('google_reviews_cache');
    }

    public function down()
    {
        $this->forge->dropTable('google_reviews_cache');
    }
}
