<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingRefundModel extends Model
{
    protected $table            = 'booking_refunds';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    protected $allowedFields = [
        'booking_id',
        'booking_service_id',
        'booking_additional_service_id',
        'booking_adjustment_id',
        'payment_id',
        'refund_scope',
        'refund_type',
        'status',
        'refund_method',
        'gateway_refund_id',
        'reason',
        'notes',
        'base_amount',
        'discount_amount',
        'taxable_amount',
        'cgst_rate',
        'sgst_rate',
        'cgst_amount',
        'sgst_amount',
        'total_refund_amount',
        'requested_by_type',
        'requested_by_id',
        'processed_by_type',
        'processed_by_id',
        'processed_at',
    ];

    protected $validationRules = [
        'booking_id' => 'required|integer',
        'booking_service_id' => 'permit_empty|integer',
        'booking_additional_service_id' => 'permit_empty|integer',
        'booking_adjustment_id' => 'permit_empty|integer',
        'payment_id' => 'permit_empty|integer',
        'refund_scope' => 'required|in_list[booking,booking_service,additional_service]',
        'refund_type' => 'required|in_list[full,partial]',
        'status' => 'required|in_list[pending,approved,processed,failed,cancelled]',
        'refund_method' => 'permit_empty|in_list[original_source,wallet,bank_transfer,manual]',
        'gateway_refund_id' => 'permit_empty|max_length[100]',
        'base_amount' => 'required|decimal',
        'discount_amount' => 'permit_empty|decimal',
        'taxable_amount' => 'permit_empty|decimal',
        'cgst_rate' => 'permit_empty|decimal',
        'sgst_rate' => 'permit_empty|decimal',
        'cgst_amount' => 'permit_empty|decimal',
        'sgst_amount' => 'permit_empty|decimal',
        'total_refund_amount' => 'required|decimal',
        'requested_by_type' => 'required|in_list[admin,customer,system]',
        'requested_by_id' => 'permit_empty|integer',
        'processed_by_type' => 'permit_empty|in_list[admin,system]',
        'processed_by_id' => 'permit_empty|integer',
        'processed_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    protected $validationMessages = [
        'booking_id' => [
            'required' => 'Booking ID is required.',
            'integer' => 'Booking ID must be a number.',
        ],
        'refund_scope' => [
            'required' => 'Refund scope is required.',
            'in_list' => 'Refund scope must be booking, booking_service, or additional_service.',
        ],
        'refund_type' => [
            'required' => 'Refund type is required.',
            'in_list' => 'Refund type must be full or partial.',
        ],
        'status' => [
            'required' => 'Refund status is required.',
            'in_list' => 'Invalid refund status provided.',
        ],
        'refund_method' => [
            'in_list' => 'Invalid refund method provided.',
        ],
        'base_amount' => [
            'required' => 'Base amount is required.',
            'decimal' => 'Base amount must be a decimal value.',
        ],
        'total_refund_amount' => [
            'required' => 'Total refund amount is required.',
            'decimal' => 'Total refund amount must be a decimal value.',
        ],
        'requested_by_type' => [
            'required' => 'Requested by type is required.',
            'in_list' => 'Requested by type must be admin, customer, or system.',
        ],
        'processed_at' => [
            'valid_date' => 'Processed at must be in Y-m-d H:i:s format.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;
}
