<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerJobAdjustments extends Migration
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

            'partner_job_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],

            'adjustment_type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'bonus',
                    'penalty',
                    'correction'
                ],
            ],

            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],

            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'is_addition' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => '1 = add, 0 = subtract',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'pending',
                    'approved',
                    'rejected'
                ],
                'default' => 'pending',
            ],

            'approved_by' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'system'],
                'null'       => true,
            ],

            'approved_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],

            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'note' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);

        $this->forge->addForeignKey(
            'partner_job_id',
            'partner_jobs',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('partner_job_adjustments', true);
    }

    public function down()
    {
        $this->forge->dropTable('partner_job_adjustments', true);
    }
}
