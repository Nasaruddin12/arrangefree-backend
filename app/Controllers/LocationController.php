<?php

namespace App\Controllers;

use App\Models\LocationCacheModel;
use CodeIgniter\RESTful\ResourceController;

class LocationController extends ResourceController
{

    public function reverseGeocode()
    {
        // 🔹 Round to reduce duplicate cache entries (~11m accuracy)
        $lat = round((float) $this->request->getVar('lat'), 4);
        $lng = round((float) $this->request->getVar('lng'), 4);

        if (!$lat || !$lng) {
            return $this->respond([
                'status' => false,
                'message' => 'Latitude and longitude required'
            ], 400);
        }

        $cacheModel = new LocationCacheModel();

        // 1️⃣ Check DB cache first
        $cached = $cacheModel->where([
            'lat' => $lat,
            'lng' => $lng
        ])->first();

        if ($cached) {
            return $this->respond([
                'status'  => true,
                'address' => $cached['address'],
                'cached'  => true
            ]);
        }

        // 2️⃣ Call Google API (IPv4 forced)
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$apiKey}";

        $client = \Config\Services::curlrequest([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
            ],
            'timeout' => 10
        ]);

        $response = $client->get($url);
        $result = json_decode($response->getBody(), true);

        if (empty($result['results'][0]['formatted_address'])) {
            return $this->respond([
                'status'  => false,
                'message' => $result['status'] ?? 'Address not found'
            ], 404);
        }

        $address = $result['results'][0]['formatted_address'];

        // 3️⃣ Save to DB cache
        $cacheModel->insert([
            'lat'     => $lat,
            'lng'     => $lng,
            'address' => $address,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->respond([
            'status'  => true,
            'address' => $address,
            'cached'  => false
        ]);
    }
    public function searchPlaces()
    {
        $query = trim($this->request->getGet('query'));
        $lat   = $this->request->getGet('lat');
        $lng   = $this->request->getGet('lng');

        if (!$query) {
            return $this->respond([
                'status' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        $apiKey = env('GOOGLE_MAPS_API_KEY');

        // Optional location bias (better nearby results)
        $locationBias = '';
        if ($lat && $lng) {
            $locationBias = "&location={$lat},{$lng}&radius=50000";
        }

        $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json"
            . "?input=" . urlencode($query)
            . $locationBias
            . "&components=country:in"
            . "&key={$apiKey}";


        // 🔒 Force IPv4
        $client = \Config\Services::curlrequest([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
            ],
            'timeout' => 10
        ]);

        $response = $client->get($url);
        $result   = json_decode($response->getBody(), true);

        if (empty($result['predictions'])) {
            return $this->respond([
                'status' => true,
                'data'   => []
            ]);
        }

        // Clean response for frontend
        $places = array_map(function ($item) {
            return [
                'place_id'   => $item['place_id'],
                'main_text'  => $item['structured_formatting']['main_text'] ?? '',
                'secondary'  => $item['structured_formatting']['secondary_text'] ?? '',
                'description' => $item['description'],
            ];
        }, $result['predictions']);

        return $this->respond([
            'status' => true,
            'data'   => $places
        ]);
    }
}
