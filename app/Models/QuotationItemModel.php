<?php

namespace App\Models;

use CodeIgniter\Model;

class QuotationItemModel extends Model
{
    protected $table = 'quotation_items';
    protected $primaryKey = 'id';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'quotation_id',
        'title',
        'description',
        'size',
        'quantity',
        'type',
        'rate',
        'amount',
        'parent_id',
    ];

    /**
     * Fetch all items for a specific quotation, including grouped sub-items.
     *
     * @param int $quotationId
     * @return array
     */
    public function getItemsByQuotation($quotationId)
    {
        $builder = $this->db->table($this->table);
        $builder->where('quotation_id', $quotationId);
        $items = $builder->get()->getResultArray();

        $groupedItems = [];
        foreach ($items as $item) {
            if (is_null($item['parent_id'])) {
                $groupedItems[$item['id']] = $item;
                $groupedItems[$item['id']]['subfiled'] = [];
            } else {
                $groupedItems[$item['parent_id']]['subfiled'][] = $item;
            }
        }

        return array_values($groupedItems);
    }
}
