<?php

namespace App\Models;

use CodeIgniter\Model;

class LocationCacheModel extends Model
{
    protected $table      = 'location_cache';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'lat',
        'lng',
        'address',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get cached address by lat/lng
     */
    public function getByLatLng(float $lat, float $lng)
    {
        return $this->where([
            'lat' => $lat,
            'lng' => $lng,
        ])->first();
    }

    /**
     * Save location if not exists
     */
    public function saveLocation(float $lat, float $lng, string $address)
    {
        return $this->insert([
            'lat'     => $lat,
            'lng'     => $lng,
            'address' => $address,
        ]);
    }
}
