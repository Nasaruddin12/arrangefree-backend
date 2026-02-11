<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerJobsModel extends Model
{
    protected $table      = 'partner_jobs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'job_id',
        'title',
        'notes',
        'booking_id',
        'partner_id',
        'assigned_by_admin_id',
        'status',
        'stopped_by',
        'stop_reason',
        'total_partner_amount',
        'estimated_start_date',
        'estimated_completion_date',
        'assigned_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'job_id' => 'required|is_natural_no_zero',
        'title' => 'permit_empty|string|max_length[255]',
        'notes' => 'permit_empty|string',
        'booking_id' => 'required|is_natural_no_zero',
        'partner_id' => 'permit_empty|is_natural_no_zero',
        'status' => 'required|in_list[pending,assigned,accepted,in_progress,partially_completed,completed,cancelled]',
        'stopped_by' => 'permit_empty|in_list[partner,admin,customer,system]',
        'total_partner_amount' => 'permit_empty|decimal',
        'estimated_start_date' => 'permit_empty|valid_date[Y-m-d]',
        'estimated_completion_date' => 'permit_empty|valid_date[Y-m-d]',
    ];
    protected $validationMessages = [
        'job_id' => [
            'required' => 'Job ID is required.',
            'is_natural_no_zero' => 'Job ID must be a natural number greater than zero.'
        ],
        'booking_id' => [
            'required' => 'Booking ID is required.',
            'is_natural_no_zero' => 'Booking ID must be a natural number greater than zero.'
        ],
        'partner_id' => [
            'is_natural_no_zero' => 'Partner ID must be a natural number greater than zero.'
        ],
        'status' => [
            'required' => 'Status is required.',
            'in_list' => 'Status must be one of: pending, assigned, accepted, in_progress, partially_completed, completed, cancelled.'
        ],
        'stopped_by' => [
            'in_list' => 'Stopped By must be one of: partner, admin, customer, system.'
        ],
        'total_partner_amount' => [
            'decimal' => 'Total Partner Amount must be a decimal value.'
        ],
        'estimated_start_date' => [
            'valid_date' => 'Estimated Start Date must be a valid date in YYYY-MM-DD format.'
        ],
        'estimated_completion_date' => [
            'valid_date' => 'Estimated Completion Date must be a valid date in YYYY-MM-DD format.'
        ],
    ];
}
