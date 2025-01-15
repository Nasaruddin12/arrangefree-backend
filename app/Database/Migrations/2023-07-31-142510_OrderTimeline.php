<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class OrderTimeline extends Migration
{
    public function up()
    {
        $this->forge->addField([
            "id" => [
                "type" => "INT",
                "unsigned" => true,
                "auto_increment" => true,
            ],
            "order_id" => [
                "type" => "INT",
                "unsigned" => true,
            ],
            "status" => [
                "type" => "INT",
                "unsigned" => true,
            ],
            "timeline" => [
                "type" => "VARCHAR",
                "constraint" => 21,
            ],
            "remark" => [
                "type" => "VARCHAR",
                "constraint" => 101,
            ],
        ]);
        $this->forge->addPrimaryKey("id");
        $this->forge->createTable("orders_timeline");
    }

    public function down()
    {
        $this->forge->dropTable("orders_timeline");
    }
}
