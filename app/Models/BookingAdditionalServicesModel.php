<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingAdditionalServicesModel extends Model
{
    protected $table            = 'booking_additional_services';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'booking_id',
        'parent_booking_service_id',
        'service_id',
        'addon_id',
        'service_type_id',
        'room_id',
        'quantity',
        'base_rate',
        'base_amount',
        'offer_id',
        'offer_discount',
        'unit',
        'rate',
        'amount',
        'cgst_rate',
        'sgst_rate',
        'cgst_amount',
        'sgst_amount',
        'total_amount',
        'room_length',
        'room_width',
        'status',
        'is_payment_required',
        'created_by',
        'created_by_id',
        'approved_at',
        'approved_by',
        'approved_by_id'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
    // Relationships
    public function booking()
    {
        return $this->belongsTo('App\Models\Booking', 'booking_id', 'id');
    }

    public function parentBookingService()
    {
        return $this->belongsTo('App\Models\BookingAdditionalService', 'parent_booking_service_id', 'id');
    }

    public function service()
    {
        return $this->belongsTo('App\Models\Service', 'service_id', 'id');
    }

    public function serviceType()
    {
        return $this->belongsTo('App\Models\ServiceType', 'service_type_id', 'id');
    }

    public function room()
    {
        return $this->belongsTo('App\Models\Room', 'room_id', 'id');
    }

    public function childServices()
    {
        return $this->hasMany('App\Models\BookingAdditionalService', 'parent_booking_service_id', 'id');
    }
}
