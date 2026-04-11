<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterChannelPartnersOtpLength extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('channel_partners', [
            'otp' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('channel_partners', [
            'otp' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
        ]);
    }
}
