<?php

namespace App\Controllers;

use App\Models\GoogleReviewsCacheModel;
use App\Models\LocationCacheModel;
use CodeIgniter\RESTful\ResourceController;

class LocationController extends ResourceController
{
    public function googleReviews()
    {
        $placeId = trim((string) ($this->request->getGet('place_id') ?? 'ChIJS3IOc5LDwjsROt1fnLDj5ks'));
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $cacheTtl = (int) (env('GOOGLE_REVIEWS_CACHE_TTL') ?: 86400);

        if ($placeId === '') {
            return $this->respond([
                'status' => false,
                'message' => 'place_id is required'
            ], 400);
        }

        if (empty($apiKey)) {
            return $this->respond([
                'status' => false,
                'message' => 'Google Maps API key is not configured'
            ], 500);
        }

        $cacheModel = new GoogleReviewsCacheModel();
        $cached = $cacheModel->where('place_id', $placeId)->first();

        if ($cached) {
            $createdAt = isset($cached['created_at']) ? strtotime((string) $cached['created_at']) : false;
            $isFresh = $createdAt !== false && (time() - $createdAt) < $cacheTtl;

            if ($isFresh && !empty($cached['response_json'])) {
                $cachedResponse = json_decode((string) $cached['response_json'], true);

                if (is_array($cachedResponse)) {
                    $cachedResponse['cached'] = true;
                    return $this->respond($cachedResponse);
                }
            }
        }

        $url = "https://maps.googleapis.com/maps/api/place/details/json"
            . "?place_id=" . urlencode($placeId)
            . "&fields=name,rating,user_ratings_total,reviews"
            . "&key=" . urlencode($apiKey);

        $client = \Config\Services::curlrequest([
            'curloptions' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
            ],
            'timeout' => 10
        ]);

        $response = $client->get($url);
        $result = json_decode($response->getBody(), true);

        if (!is_array($result)) {
            return $this->respond([
                'status' => false,
                'message' => 'Unable to fetch Google reviews'
            ], 502);
        }

        $payload = $result;
        $payload['cached'] = false;

        $cacheData = [
            'place_id' => $placeId,
            'response_json' => json_encode($result),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($cached) {
            $cacheModel->update($cached['id'], $cacheData);
        } else {
            $cacheModel->insert($cacheData);
        }

        return $this->respond($payload ?? [
            'status' => false,
            'message' => 'Unable to fetch Google reviews'
        ]);
    }

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
            'curloptions' => [
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
        $query = trim($this->request->getVar('query'));
        $lat   = $this->request->getVar('lat');
        $lng   = $this->request->getVar('lng');

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
            'curloptions' => [
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
