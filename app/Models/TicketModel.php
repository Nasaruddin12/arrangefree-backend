<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketModel extends Model
{
    protected $table      = 'tickets';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'ticket_uid',
        'user_type',
        'user_id',
        'partner_id',
        'booking_id',
        'task_id',
        'subject',
        'file',
        'status',
        'priority',
        'category',
        'assigned_admin_id',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_type' => 'required|in_list[customer,partner]',
        'user_id' => 'permit_empty|integer', // ✅ Removed "required"
        'partner_id' => 'permit_empty|integer',
        'subject' => 'required|string|max_length[255]',
        'priority' => 'required|in_list[low,medium,high]',
        'category' => 'required',
        'status' => 'permit_empty|in_list[open,in_progress,closed]',
        'booking_id' => 'permit_empty|integer',
        'task_id' => 'permit_empty|integer',
        'assigned_admin_id' => 'permit_empty|integer',
        'file' => 'permit_empty'
    ];

    protected $validationMessages = [
        'user_type' => [
            'required' => 'User type is required.',
            'in_list'  => 'User type must be either customer or partner.'
        ],
        'user_id' => [
            'integer'  => 'User ID must be a valid number.'
        ],
        'partner_id' => [
            'integer'  => 'Partner ID must be a valid number.'
        ],
        'subject' => [
            'required'    => 'Subject is required.',
            'max_length'  => 'Subject must not exceed 255 characters.'
        ],
        'priority' => [
            'required' => 'Priority is required.',
            'in_list'  => 'Priority must be one of: low, medium, or high.'
        ],
        'category' => [
            'required' => 'Category is required.',
        ],
        'status' => [
            'in_list' => 'Status must be one of: open, in_progress, or closed.'
        ],
    ];

    /**
     * Generate a unique ticket UID in format TCKT-YY#### (e.g. TCKT-240001)
     */
    public function generateTicketUID(): string
    {
        $yearPrefix = date('y');

        try {
            $builder = $this->builder();
            $builder->select('ticket_uid');
            $builder->like('ticket_uid', "TCKT-$yearPrefix", 'after');
            $builder->orderBy('id', 'DESC');
            $builder->limit(1);

            $query = $builder->get();

            if (!$query) {
                throw new \RuntimeException('Failed to execute UID query.');
            }

            $lastTicket = $query->getRowArray();

            if (
                $lastTicket && isset($lastTicket['ticket_uid']) &&
                preg_match('/TCKT-\d{2}(\d{4})/', $lastTicket['ticket_uid'], $matches)
            ) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }

            return 'TCKT-' . $yearPrefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            log_message('error', 'generateTicketUID error: ' . $e->getMessage());
            return 'TCKT-' . $yearPrefix . '0001'; // fallback if query fails
        }
    }
}
