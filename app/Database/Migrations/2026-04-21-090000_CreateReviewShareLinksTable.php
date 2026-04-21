<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReviewShareLinksTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'booking_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'token_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 24,
                'null'       => true,
            ],
            'created_by_admin_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'revoked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('booking_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_by_admin_id');
        $this->forge->addUniqueKey('token_hash', 'uniq_review_share_links_token_hash');
        $this->forge->addUniqueKey('code', 'uniq_review_share_links_code');

        $this->forge->createTable('review_share_links');
    }

    public function down()
    {
        $this->forge->dropTable('review_share_links');
    }
}
