<?php namespace App\Libraries;

use Google\Auth\OAuth2;
use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;

class FirebaseService
{
    private $projectId;
    private $credentials;
    private $accessToken;
    private $client;

    public function __construct()
    {
        $this->projectId = 'seeb-e3cea'; // 🔁 Replace with your actual Firebase project ID

        $keyFilePath = APPPATH . 'Config/firebase-service-account.json';        // move your JSON key here or update path

        $this->credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $keyFilePath
        );

        // Fetch OAuth token
        $this->accessToken = $this->credentials->fetchAuthToken()['access_token'];

        $this->client = new Client([
            'base_uri' => 'https://fcm.googleapis.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type'  => 'application/json',
            ]
        ]);
    }

    public function sendNotification($fcmToken, $title, $body)
    {
        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ]
            ]
        ];

        $response = $this->client->post("projects/{$this->projectId}/messages:send", [
            'json' => $payload
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
