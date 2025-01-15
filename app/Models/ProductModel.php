<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'af_products';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'home_zone_appliances_id',
        'home_zone_category_id',
        'brand_id',
        'name',
        'actual_price',
        'discounted_percent',
        'increase_percent',
        'height',
        'size',
        'warranty',
        'product_code',
        'features',
        'properties',
        'quantity',
        'care_n_instructions',
        'warranty_details',
        'quality_promise',
        'vendor_name',
        'status',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected function apply_discount($data)
    {
        $metadataModel = new MetaDataModel();
        $discountPercent = $metadataModel->find(1);
        if (empty($discountPercent)) {
            return $data;
        }
        $discountPercent = $discountPercent['value'];
        $data['data']['discounted_percent'] = (int)$discountPercent;
        // die(print_r($data));
        return $data;
    }

    protected function increase_price($data)
    {
        $metadataModel = new MetaDataModel();
        $discountPercent = $metadataModel->find(6);
        if (empty($discountPercent)) {
            return $data;
        }
        $discountPercent = $discountPercent['value'];
        $data['data']['increase_percent'] = (int)$discountPercent;
        // die(print_r($data));
        return $data;
    }

    protected $allowCallbacks = true;
    protected $beforeInsert = ['apply_discount', 'increase_price'];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];
}
