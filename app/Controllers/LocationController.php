<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class LocationController extends ResourceController
{
    public function reverseGeocode()
    {
        $lat = $this->request->getVar('lat');
        $lng = $this->request->getVar('lng');

        if (!$lat || !$lng) {
            return $this->respond([
                'status' => false,
                'message' => 'Latitude and longitude required'
            ], 400);
        }

        $apiKey = env('GOOGLE_MAPS_API_KEY');

        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$apiKey}";

        $client = \Config\Services::curlrequest();
        $response = $client->get($url);

        $result = json_decode($response->getBody(), true);
        print_r($result); exit;

        if (!empty($result['results'][0]['formatted_address'])) {
            return $this->respond([
                'status' => true,
                'address' => $result['results'][0]['formatted_address']
            ]);
        }

        return $this->respond([
            'status' => false,
            'message' => 'Address not found'
        ], 404);
    }
}
