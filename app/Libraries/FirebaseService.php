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

    public function sendNotification($tokens, $title, $body, $screen = "Home", $id = null, $unreadCount = 1, $image = null)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception('Failed to retrieve access token');
        }

        $tokens = is_array($tokens) ? $tokens : [$tokens];
        $client = new \GuzzleHttp\Client();
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $responses = [];

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    // 'notification' => [
                    //     'title' => $title,
                    //     'body'  => $body,
                    //     'image' => $image // ✅ Include image here if provided
                    // ],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'image' => $image // ✅ Optional for Android compatibility
                        ]
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10'
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'content-available' => 1,
                                'badge' => $unreadCount
                            ]
                        ]
                    ],
                    'data' => [
                        'screen'        => (string) $screen,
                        'id'            => $id !== null ? (string) $id : '',
                        'title'         => (string) $title,
                        'body'          => (string) $body,
                        'image'         => $image !== null ? (string) $image : '',
                        'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                        'sound'         => 'default'
                    ]

                ]
            ];

            try {
                $response = $client->post($url, [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                        'Content-Type'  => 'application/json'
                    ],
                    'json' => $payload
                ]);

                $responses[] = [
                    'token' => $token,
                    'status' => $response->getStatusCode(),
                    'response' => json_decode($response->getBody(), true)
                ];
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $error = $e->hasResponse()
                    ? $e->getResponse()->getBody()->getContents()
                    : $e->getMessage();
                log_message('error', 'Notification send failed: ' . $error);

                $responses[] = [
                    'token' => $token,
                    'status' => 500,
                    'error' => $error
                ];
            }
        }

        return $responses;
    }
}
