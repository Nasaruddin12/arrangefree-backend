<?php

namespace App\Libraries;

use GuzzleHttp\Client;
use Google\Auth\Credentials\ServiceAccountCredentials;

class GeminiAI
{
    protected $client;
    protected $projectId;
    protected $location;
    protected $modelName;

    public function __construct()
    {
        $this->projectId = 'gen-lang-client-0543810920';
        $this->location = 'us-central1';
        $this->modelName = 'imagen-3.0-generate-002';

        $this->client = new Client([
            'base_uri' => "https://{$this->location}-aiplatform.googleapis.com/v1/",
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ],
        ]);
    }

    protected function getAccessToken()
    {
        $keyPath = 'D:/localhost/arrange-free/app/Config/service-account.json';

        if (!file_exists($keyPath)) {
            throw new \Exception("Google service account file not found at: {$keyPath}");
        }

        $jsonKey = json_decode(file_get_contents($keyPath), true);

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/cloud-platform'],
            $jsonKey
        );

        $token = $credentials->fetchAuthToken();

        if (!isset($token['access_token'])) {
            throw new \Exception('Failed to retrieve access token from credentials.');
        }

        return $token['access_token'];
    }

    /**
     * Generate an image using Imagen predict API.
     *
     * @param string $prompt
     * @param string $aspectRatio e.g. '1:1', '16:9'
     * @return array
     */
    public function generateImage(string $prompt, string $aspectRatio = '1:1'): array
    {
        $url = "projects/{$this->projectId}/locations/{$this->location}/publishers/google/models/{$this->modelName}:predict";

        $payload = [
            'instances' => [
                [
                    'prompt' => $prompt,
                ],
            ],
            'parameters' => [
                'sampleCount' => 1,
                'aspectRatio' => $aspectRatio,
            ],
        ];

        try {
            $response = $this->client->post($url, [
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            $generatedImages = [];

            if (!empty($result['predictions'])) {
                foreach ($result['predictions'] as $index => $prediction) {
                    if (isset($prediction['bytesBase64Encoded'])) {
                        $base64Data = $prediction['bytesBase64Encoded'];
                        // print_r($base64Data);
                        // Save to /public/uploads/ to make it directly accessible
                        $uploadDir = FCPATH . 'uploads/geminiimagegen/';

                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $filename = 'imagen_' . time() . '_' . $index . '.jpg';
                        $filePath = $uploadDir . $filename;

                        // Save the image file
                        file_put_contents($filePath, base64_decode($base64Data));

                        // ✅ This is the relative path to be used in HTML <img> tag
                        $imageUrl = 'public/uploads/geminiimagegen/' . $filename;
                        // die();
                        $generatedImages[] = [
                            'prompt' => $prediction['prompt'] ?? $prompt,
                            'url' => $imageUrl,
                            'filename' => $uploadDir . $filename,
                        ];
                    }
                }
            }



            return [
                'images' => $generatedImages,
                'raw_response' => $result
            ];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            log_message('error', 'Imagen API Error: ' . $responseBody);
            throw new \Exception('Imagen API Error: ' . $responseBody);
        } catch (\Exception $e) {
            log_message('error', 'Imagen API general error: ' . $e->getMessage());
            throw new \Exception('An error occurred while generating the image.');
        }
    }
    public function generateImageWithOptionalImage(string $prompt, ?string $base64Image = null, ?string $mimeType = null): array
    {
        $url = "projects/{$this->projectId}/locations/{$this->location}/publishers/google/models/{$this->modelName}:predict";

        $body = [
            'instances' => [
                [
                    'prompt' => $prompt,
                    // Optional image input
                    ...($base64Image && $mimeType ? [
                        'image' => [
                            'bytesBase64Encoded' => $base64Image,
                            'mimeType' => $mimeType
                        ]
                    ] : [])
                ]
            ],
            'parameters' => [
                'sampleCount' => 1,
                'aspectRatio' => '1:1',
            ]
        ];


        try {
            $response = $this->client->post($url, ['json' => $body]);
            $result = json_decode($response->getBody()->getContents(), true);

            $generatedImages = [];
            if (!empty($result['predictions'])) {
                foreach ($result['predictions'] as $index => $prediction) {
                    if (isset($prediction['bytesBase64Encoded'])) {
                        $base64Data = $prediction['bytesBase64Encoded'];
                        // print_r($base64Data);
                        // Save to /public/uploads/ to make it directly accessible
                        $uploadDir = FCPATH . 'uploads/geminiimagegen/';

                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $filename = 'imagen_' . time() . '_' . $index . '.jpg';
                        $filePath = $uploadDir . $filename;

                        // Save the image file
                        file_put_contents($filePath, base64_decode($base64Data));

                        // ✅ This is the relative path to be used in HTML <img> tag
                        $imageUrl = 'public/uploads/geminiimagegen/' . $filename;
                        // die();
                        $generatedImages[] = [
                            'prompt' => $prediction['prompt'] ?? $prompt,
                            'url' => $imageUrl,
                            'filename' => $uploadDir . $filename,
                        ];
                    }
                }
            }

            return [
                'images' => $generatedImages,
                'raw_response' => $result
            ];
        } catch (\Exception $e) {
            log_message('error', 'Gemini AI Error: ' . $e->getMessage());
            throw new \Exception('Failed to generate image: ' . $e->getMessage());
        }
    }
}
