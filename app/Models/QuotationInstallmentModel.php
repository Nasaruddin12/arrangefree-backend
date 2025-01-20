<?php

namespace App\Models;

use CodeIgniter\Model;

class QuotationInstallmentModel extends Model
{
    protected $table = 'quotation_installments';
    protected $primaryKey = 'id';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'quotation_id',
        'label',
        'percentage',
        'amount',
        'due_date',
    ];

    /**
     * Fetch all installments for a specific quotation.
     *
     * @param int $quotationId
     * @return array
     */
    public function getInstallmentsByQuotation($quotationId)
    {
        return $this->where('quotation_id', $quotationId)->findAll();
    }
}
