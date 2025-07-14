<?php

namespace App\Libraries;

use GuzzleHttp\Client;

class FirebaseService
{
    private $projectId;
    private $clientEmail;
    private $privateKey;

    public function __construct()
    {
        $serviceAccount = json_decode(file_get_contents(APPPATH . 'Config/firebase-service-account.json'), true);
        if (!$serviceAccount) {
            throw new \Exception('Firebase service account file not found or invalid.');
        }
        $this->projectId = $serviceAccount['project_id'];
        $this->clientEmail = $serviceAccount['client_email'];
        $this->privateKey = str_replace("\\n", "\n", $serviceAccount['private_key']);
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function getAccessToken()
    {
        $now = time();
        $expires = $now + 3600;

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss'   => $this->clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $expires
        ];

        $jwtHeader = $this->base64UrlEncode(json_encode($header));
        $jwtClaims = $this->base64UrlEncode(json_encode($claims));
        $data = "$jwtHeader.$jwtClaims";

        openssl_sign($data, $signature, $this->privateKey, 'sha256WithRSAEncryption');
        $jwtSignature = $this->base64UrlEncode($signature);

        $jwt = "$data.$jwtSignature";

        $client = new Client();

        try {
            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt
                ]
            ]);

            $body = json_decode($response->getBody(), true);

            return $body['access_token'];
        } catch (\Exception $e) {
            print_r($e->getMessage());
            log_message('error', 'Access token fetch failed: ' . $e->getMessage());
            return null;
        }
    }

    public function sendNotification($token, $title, $body, $screen = "TicketChat", $id = "3")
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception('Failed to retrieve access token');
        }

        $client = new \GuzzleHttp\Client();
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        // Prepare data payload
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'sound'        => 'default',
        ];

  
        $message = [
            'message' => [
                'token' => $token,
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default' // ✅ Android sound
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default', // ✅ iOS sound
                            'content-available' => 1 // optional: for background notifications
                        ]
                    ]
                ],
                'data' => [ // ✅ Optional navigation support
                    'screen' => $screen ?? null,
                    'id'     => $id ?? null,
                    'title'  => $title,
                    'body'   => $body,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'sound' => 'default',
                    
                ]
            ]
        ];

        try {
            return $client->post($url, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                    'Content-Type'  => 'application/json'
                ],
                'json' => $message
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorResponse = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            log_message('error', 'Notification send failed: ' . $errorResponse);
            throw new \Exception($errorResponse, 500);
        }
    }
}
