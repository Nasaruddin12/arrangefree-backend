<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerServiceModel extends Model
{
    protected $table            = 'partner_services';
    protected $primaryKey       = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'partner_id',
        'service_id',
        'custom_price',
        'experience_years',
        'status'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';


    /*
    |--------------------------------------------------------------------------
    | Custom Methods
    |--------------------------------------------------------------------------
    */

    // Get services by partner
    public function getServicesByPartner($partnerId)
    {
        return $this->select('partner_services.*, services.name as service_name')
                    ->join('services', 'services.id = partner_services.service_id')
                    ->where('partner_services.partner_id', $partnerId)
                    ->findAll();
    }

    // Get active partners for a service (useful for assignment engine)
    public function getActivePartnersByService($serviceId)
    {
        return $this->select('partner_services.*, partners.name as partner_name')
                    ->join('partners', 'partners.id = partner_services.partner_id')
                    ->where('partner_services.service_id', $serviceId)
                    ->where('partner_services.status', 'active')
                    ->findAll();
    }
}