<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChecklistFeedback extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'auto_increment' => true],
            'assignment_checklist_id' => ['type' => 'INT', 'null' => false],
            'question_id'             => ['type' => 'INT', 'null' => false],
            'rating'                  => ['type' => 'INT', 'null' => true],
            'comment'                 => ['type' => 'TEXT', 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('assignment_checklist_id', 'booking_assignment_checklist_status', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('question_id', 'service_checklists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('checklist_feedback');
    }

    public function down()
    {
        $this->forge->dropTable('checklist_feedback');
    }
}
