<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AfRatingReview extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'customer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'product_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'rating' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'review' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
                'null' => true,
            ],
            'status' => [
                'type' => 'SMALLINT',
                'null' => false,
                'default' => 0,
                'comment' => '0-Pending, 1-Approved, 2-Rejected',
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
  
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('af_rating_review');
    }

    public function down()
    {
        $this->forge->dropTable('af_rating_review');
    }
}
