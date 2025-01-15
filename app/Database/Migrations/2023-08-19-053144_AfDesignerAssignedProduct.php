<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfDesignerAssignedProduct extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'designer_id' => [
                'type' => 'INT',
            ],
            'product_id' => [
                'type' => 'INT',
            ],
            'status' => [
                'type' => 'INT',
                'default' => '1',
                'comment' => "1 Pending , 2 Done"
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_designer_assign_products');
    }

    public function down()
    {
        $this->forge->dropTable('af_designer_assign_products');
    }
}
