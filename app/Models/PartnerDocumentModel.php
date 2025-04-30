<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerDocumentModel extends Model
{
    protected $table            = 'partner_documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'partner_id',
        'type',
        'file_path',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'partner_id' => 'required|is_natural_no_zero',
        'type'       => 'required|in_list[aadhar_front,aadhar_back,pan_card,address_proof,photo,bank_document]',
        'file_path'  => 'required',
        'status'     => 'in_list[pending,verified,rejected]',
    ];

    protected $validationMessages = [
        'type' => ['in_list' => 'Invalid document type.'],
        'file_path' => ['required' => 'File path is required.'],
    ];
}
