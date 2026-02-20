<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInitialOfferSnapshotToSeebCart extends Migration
{
    public function up()
    {
        $this->forge->addColumn('seeb_cart', [
            'initial_offer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'final_amount',
            ],
            'initial_selling_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'after' => 'initial_offer_id',
            ],
            'initial_final_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'after' => 'initial_selling_rate',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('seeb_cart', [
            'initial_offer_id',
            'initial_selling_rate',
            'initial_final_amount',
        ]);
    }
}
