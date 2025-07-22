<?php

namespace App\Libraries;

use GuzzleHttp\Client;

class FirestoreService
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
            'scope' => 'https://www.googleapis.com/auth/datastore',
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

            // Optional: include context for debugging
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());

            // Throw the exception or return a CI4-friendly response
            throw new \RuntimeException('Unable to fetch Firebase access token. Please check credentials.');
        }
    }



    private function makeFirestoreRequest($documentName, $payload, $method)
    {
        $fields = ['status', 'updated_at', 'booking_service_id', 'partner_id', 'firebase_uid', 'amount', 'timestamp', 'service_name', 'customer_name', 'slot_date'];
        $maskQuery = implode('&updateMask.fieldPaths=', $fields);
        $url = "https://firestore.googleapis.com/v1/$documentName?updateMask.fieldPaths=" . implode('&updateMask.fieldPaths=', $fields);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->getAccessToken(),
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            // Log detailed error
            log_message('error', "Firestore request failed: HTTP $httpCode | URL: $url | Response: $response | Curl Error: $error");

            // Throw to abort further processing
            throw new \RuntimeException("Failed to write assignment to Firestore. HTTP $httpCode: $response");
        }

        return true;
    }

    public function pushAssignmentRequest($bookingServiceId, $firebaseUid, $partnerId, $assignedAmount = null, $serviceName = null, $customerName = null, $slotDate = null)
    {
        $documentName = "projects/{$this->projectId}/databases/(default)/documents/booking_requests/{$firebaseUid}_{$bookingServiceId}";

        $payload = [
            'fields' => [
                'booking_service_id' => ['stringValue' => (string) $bookingServiceId],
                'service_name'       => ['stringValue' => (string) $serviceName],
                'customer_name'      => ['stringValue' => (string) $customerName],
                'slot_date'          => ['timestampValue' => $slotDate],
                'firebase_uid'       => ['stringValue' => (string) $firebaseUid],
                'partner_id'        => ['stringValue' => (string) $partnerId],
                'amount'             => $assignedAmount !== null ? ['doubleValue' => (float) $assignedAmount] : ['nullValue' => null],
                'status'             => ['stringValue' => 'pending'],
                'timestamp'          => ['timestampValue' => date('c')]
            ]
        ];

        $this->makeFirestoreRequest($documentName, $payload, 'PATCH');
    }

    public function updateStatus($bookingServiceId, $firebaseUid, $status)
    {
        $documentName = "projects/{$this->projectId}/databases/(default)/documents/booking_requests/{$firebaseUid}_{$bookingServiceId}";

        $payload = [
            'fields' => [
                'status'     => ['stringValue' => $status],
                'updated_at' => ['timestampValue' => date('c')]
            ]
        ];

        $this->makeFirestoreRequest($documentName, $payload, 'PATCH');
    }
}
