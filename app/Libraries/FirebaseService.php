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
            log_message('error', 'Access token fetch failed: ' . $e->getMessage());
            return null;
        }
    }

    public function sendNotification($token, $title, $body)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return ['error' => 'Failed to retrieve access token'];
        }

        $client = new Client();
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body
                ],
                'android' => [
                    'priority' => 'high'
                ]
            ]
        ];

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                    'Content-Type'  => 'application/json'
                ],
                'json' => $message
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            log_message('error', 'Notification send failed: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
