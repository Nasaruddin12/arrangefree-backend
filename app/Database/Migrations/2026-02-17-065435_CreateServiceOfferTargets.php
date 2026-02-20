<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceOfferTargets extends Migration
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

            'offer_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'target_type' => [
                'type'       => 'ENUM',
                'constraint' => ['service', 'category', 'global'],
            ],

            'service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('offer_id');
        $this->forge->addKey('service_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey('target_type');

        // Foreign key to service_offers
        $this->forge->addForeignKey(
            'offer_id',
            'service_offers',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('service_offer_targets');
    }

    public function down()
    {
        $this->forge->dropTable('service_offer_targets');
    }
}
