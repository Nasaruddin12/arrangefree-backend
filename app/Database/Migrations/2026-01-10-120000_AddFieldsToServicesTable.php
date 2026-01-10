<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldsToServicesTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('services', [
            'primary_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'status'
            ],
            'secondary_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'primary_key'
            ],
            'partner_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'after'      => 'secondary_key'
            ],
            'with_material' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'null'       => false,
                'after'      => 'partner_price'
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'unique'     => true,
                'after'      => 'with_material'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('services', ['primary_key', 'secondary_key', 'partner_price', 'with_material', 'slug']);
    }
}
