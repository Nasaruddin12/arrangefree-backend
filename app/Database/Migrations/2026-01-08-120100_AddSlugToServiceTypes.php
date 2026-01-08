<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSlugToServiceTypes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('service_types', [
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
        $this->forge->dropColumn('service_types', 'slug');
    }
}
