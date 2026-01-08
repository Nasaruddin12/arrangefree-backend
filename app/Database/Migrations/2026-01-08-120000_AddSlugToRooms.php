<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSlugToRooms extends Migration
{
    public function up()
    {
        $this->forge->addColumn('rooms', [
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'unique'     => true,
                'after'      => 'name'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('rooms', 'slug');
    }
}
