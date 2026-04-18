<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLeadAssignmentSupport extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('assigned_admin_id', 'channel_partner_leads')) {
            $this->forge->addColumn('channel_partner_leads', [
                'assigned_admin_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'channel_partner_id',
                ],
                'assigned_by_admin_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'assigned_admin_id',
                ],
                'assigned_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'assigned_by_admin_id',
                ],
                'last_follow_up_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'assigned_at',
                ],
            ]);
        }

        if ($this->db->fieldExists('assigned_admin_id', 'channel_partner_leads')) {
            $this->forge->modifyColumn('channel_partner_leads', [
                'assigned_admin_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
        }

        $this->addForeignKeyIfMissing(
            'channel_partner_leads',
            'fk_channel_partner_leads_assigned_admin_id',
            'assigned_admin_id',
            'admins',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKeyIfMissing(
            'channel_partner_leads',
            'fk_channel_partner_leads_assigned_by_admin_id',
            'assigned_by_admin_id',
            'admins',
            'id',
            'SET NULL',
            'CASCADE'
        );

        if (!$this->db->tableExists('channel_partner_lead_follow_ups')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'lead_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'assigned_admin_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'admin_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'previous_status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'next_follow_up_at' => [
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
            $this->forge->addKey('lead_id');
            $this->forge->addKey('assigned_admin_id');
            $this->forge->addKey('admin_id');
            $this->forge->createTable('channel_partner_lead_follow_ups', true);
        }

        if ($this->db->fieldExists('assigned_admin_id', 'channel_partner_lead_follow_ups')) {
            $this->forge->modifyColumn('channel_partner_lead_follow_ups', [
                'assigned_admin_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
        }

        $this->addForeignKeyIfMissing(
            'channel_partner_lead_follow_ups',
            'fk_channel_partner_lead_follow_ups_lead_id',
            'lead_id',
            'channel_partner_leads',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKeyIfMissing(
            'channel_partner_lead_follow_ups',
            'fk_channel_partner_lead_follow_ups_assigned_admin_id',
            'assigned_admin_id',
            'admins',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKeyIfMissing(
            'channel_partner_lead_follow_ups',
            'fk_channel_partner_lead_follow_ups_admin_id',
            'admin_id',
            'admins',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function down()
    {
        if ($this->db->tableExists('channel_partner_lead_follow_ups')) {
            $this->forge->dropTable('channel_partner_lead_follow_ups', true);
        }

        if ($this->db->fieldExists('last_follow_up_at', 'channel_partner_leads')) {
            $this->dropForeignKeyIfExists('channel_partner_leads', 'fk_channel_partner_leads_assigned_admin_id');
            $this->dropForeignKeyIfExists('channel_partner_leads', 'fk_channel_partner_leads_assigned_by_admin_id');
            $this->forge->dropColumn('channel_partner_leads', ['assigned_admin_id', 'assigned_by_admin_id', 'assigned_at', 'last_follow_up_at']);
        }
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $constraintName,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        string $onDelete,
        string $onUpdate
    ): void {
        if ($this->constraintExists($table, $constraintName)) {
            return;
        }

        $this->db->query(
            sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s ON UPDATE %s',
                $table,
                $constraintName,
                $column,
                $referenceTable,
                $referenceColumn,
                $onDelete,
                $onUpdate
            )
        );
    }

    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        if (!$this->constraintExists($table, $constraintName)) {
            return;
        }

        $this->db->query(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraintName));
    }

    private function constraintExists(string $table, string $constraintName): bool
    {
        $row = $this->db->query(
            'SELECT CONSTRAINT_NAME
             FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
             LIMIT 1',
            [$table, $constraintName]
        )->getRowArray();

        return !empty($row);
    }
}
