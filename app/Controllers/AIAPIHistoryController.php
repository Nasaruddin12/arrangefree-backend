<?php

namespace App\Controllers;

use App\Libraries\GeminiAI;
use App\Models\AIAPIHistoryModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Config\Database;

class AIAPIHistoryController extends ResourceController
{
    public function getHistoryByUser($userId)
    {
        try {
            // Validate user ID
            if (!is_numeric($userId) || $userId <= 0) {
                return $this->failValidationErrors('Invalid user ID provided.');
            }

            $aiApiHistoryModel = new AIAPIHistoryModel();
            $historyData = $aiApiHistoryModel->where('user_id', $userId)->findAll();

            if (!empty($historyData)) {
                return $this->respond([
                    'status'  => 200,
                    'message' => 'AI API history retrieved successfully.',
                    'data'    => $historyData
                ], 200);
            } else {
                return $this->respond([
                    'status'  => 404,
                    'message' => 'No AI API history found for this user.',
                    'data'    => []
                ], 404);
            }
        } catch (\Throwable $e) {
            return $this->failServerError('An error occurred while fetching AI API history: ' . $e->getMessage());
        }
    }

    public function analyzeImage()
    {
        try {
            $rules = [
                'user_id'   => 'required|integer',
                'image_url' => 'required|valid_url',
            ];

            $input = $this->request->getJSON(true);

            if (!$this->validate($rules)) {
                return $this->response->setJSON([
                    'status'  => 422,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors(),
                ])->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
            }


            $imageUrl = $input['image_url'];
            $apiKey = getenv('OPENAI_API_KEY'); // <-- Securely from .env
            if (empty($apiKey)) {
                return $this->response->setJSON([
                    'status'  => 500,
                    'message' => 'OpenAI API key is missing in environment config.',
                ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $data = [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        "role" => "system",
                        "content" => "Analyze the interior design image and extract a detailed material breakdown based **only on visible elements**. Follow strict commercial standards: \n\n✅ Provide paint, laminate, lighting, fabric, and material details. \n✅ Always include **Asian Paints color codes** for walls & ceilings.\n✅ Always include **Royale Touche & Merino laminate codes** for wardrobes, wood paneling, and furniture.\n✅ Always include **Wipro Lights** for all lighting fixtures.\n✅ Always specify **wood type** (Pine, Teak).\n✅ Always specify **kitchen material** (Shore Acrylic, Merino Acrylic).\n✅ Always specify **curtains** from **D'Decor** fabrics with color codes."
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image_url',
                                'image_url' => ['url' => $imageUrl]
                            ]
                        ]
                    ]
                ]
            ];

            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ];

            $client = \Config\Services::curlrequest();

            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => $headers,
                'body'    => json_encode($data),
            ]);

            $responseData = json_decode($response->getBody(), true);
            // dd($responseData);
            // print_r($responseData);
            $aiResponse = 'No response received';

            if (
                isset($responseData['choices'][0]['message']['content']) &&
                !empty($responseData['choices'][0]['message']['content'])
            ) {
                $aiResponse = $responseData['choices'][0]['message']['content'];
            } else {
                log_message('error', 'OpenAI unexpected response: ' . json_encode($responseData));
            }


            // Save to DB
            $historyData = [
                'user_id'       => $input['user_id'],
                'api_endpoint'  => 'https://api.openai.com/v1/chat/completions',
                'request_data'  => json_encode($data),
                'response_data' => $aiResponse,
                'status_code'   => $response->getStatusCode(),
            ];

            $aiApiHistoryModel = new \App\Models\AIAPIHistoryModel();
            $aiApiHistoryModel->insert($historyData);

            return $this->response->setJSON([
                'status'  => 200,
                'message' => 'AI response fetched and saved successfully',
                'data'    => [
                    'response' => $aiResponse,
                    'history_id' => $aiApiHistoryModel->getInsertID(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status'  => 500,
                'message' => 'API Request Failed',
                'error'   => $e->getMessage(),
            ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAll()
    {
        try {
            $db = Database::connect();
            $model = new AIAPIHistoryModel();

            // Get query parameters
            $page = (int) ($this->request->getVar('page') ?? 1);
            $perPage = (int) ($this->request->getVar('perPage') ?? 10);
            $search = $this->request->getVar('search');
            $startDate = $this->request->getVar('start_date');
            $endDate = $this->request->getVar('end_date');
            $offset = max(0, ($page - 1) * $perPage);

            // Subquery to get latest usage per user
            $subQuery = $db->table('ai_api_history')
                ->select('user_id, MAX(created_at) as latest_usage')
                ->groupBy('user_id')
                ->getCompiledSelect();

            // Main query: Get customers with their latest API usage
            $query = $db->table('ai_api_history')
                ->select('af_customers.id AS user_id, af_customers.name, recent_usage.latest_usage AS created_at, COUNT(ai_api_history.id) AS usage_count')
                ->join("($subQuery) AS recent_usage", 'recent_usage.user_id = ai_api_history.user_id', 'inner')
                ->join('af_customers', 'af_customers.id = ai_api_history.user_id', 'inner')
                ->groupBy('ai_api_history.user_id')
                ->orderBy('recent_usage.latest_usage', 'DESC');

            // Apply search filter
            if (!empty($search)) {
                $query->like('af_customers.name', $search);
            }

            // Apply date range filter
            if (!empty($startDate) && !empty($endDate)) {
                $query->where('ai_api_history.created_at >=', date('Y-m-d', strtotime($startDate)))
                    ->where('ai_api_history.created_at <=', date('Y-m-d', strtotime($endDate)));
            }

            // Get total unique customer count
            $totalRecordsQuery = clone $query;
            $totalRecords = count($totalRecordsQuery->get()->getResultArray());

            // Apply pagination
            $data = $query->limit($perPage, $offset)->get()->getResultArray();

            // Check if data exists
            if (empty($data)) {
                return $this->failNotFound('No customers found who used the AI API.');
            }

            return $this->respond([
                "status" => 200,
                "message" => "Data retrieved successfully",
                "data" => $data,
                "pagination" => [
                    "currentPage" => $page,
                    "perPage" => $perPage,
                    "totalPages" => ceil($totalRecords / $perPage),
                    "totalRecords" => $totalRecords
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('An unexpected error occurred: ' . $e->getMessage());
        }
    }


    public function gpt4oMini()
    {
        try {
            $rules = [
                'user_id' => 'required|integer',
                'prompt'  => 'required|string|max_length[2000]',
            ];

            $input = $this->request->getJSON(true);

            // Validate
            if (!$this->validate($rules)) {
                return $this->response->setJSON([
                    'status'  => 422,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors(),
                ])->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
            }

            $userId     = $input['user_id'];
            $userPrompt = $input['prompt'];
            $imageUrl   = $input['image_url'] ?? null; // optional

            $apiKey = getenv('OPENAI_API_KEY');
            if (empty($apiKey)) {
                return $this->response->setJSON([
                    'status'  => 500,
                    'message' => 'OpenAI API key is missing in environment config.',
                ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Build messages array
            $messages = [
                [
                    'role' => 'user',
                    'content' => $imageUrl
                        ? [
                            ['type' => 'text', 'text' => $userPrompt],
                            ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]]
                        ]
                        : $userPrompt
                ]
            ];

            $data = [
                'model' => 'gpt-4o',
                'messages' => $messages,
            ];

            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ];

            $client = \Config\Services::curlrequest();

            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => $headers,
                'body'    => json_encode($data),
            ]);
            $responseData = json_decode($response->getBody(), true);
            $content = $responseData['choices'][0]['message']['content'] ?? 'No content generated.';

            return $this->response->setJSON([
                'status'  => 200,
                'message' => 'AI analysis completed successfully.',
                'data' => $content,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'GPT-4o API Error: ' . $e->getMessage());
            return $this->failServerError('An error occurred while processing the request: ' . $e->getMessage());
        }
    }
    
    public function dalleImageGenerate()
    {
        try {
            $rules = [
                'user_id' => 'required|integer',
                'prompt'  => 'required|string|max_length[1000]',
            ];

            $input = $this->request->getJSON(true);

            if (!$this->validate($rules)) {
                return $this->response->setJSON([
                    'status'  => 422,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors(),
                ])->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
            }

            $userId     = $input['user_id'];
            $userPrompt = $input['prompt'];

            $apiKey = getenv('OPENAI_API_KEY');
            if (empty($apiKey)) {
                return $this->response->setJSON([
                    'status'  => 500,
                    'message' => 'OpenAI API key is missing in environment config.',
                ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $data = [
                'model' => 'dall-e-3',
                'prompt' => $userPrompt,
                'n' => 1, // Number of images
                'size' => '1024x1024',
                'response_format' => 'url' // or 'b64_json' for base64
            ];

            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ];

            $client = \Config\Services::curlrequest();

            $response = $client->post('https://api.openai.com/v1/images/generations', [
                'headers' => $headers,
                'body'    => json_encode($data),
            ]);

            $responseData = json_decode($response->getBody(), true);

            $imageUrl = $responseData['data'][0]['url'] ?? null;

            if (!$imageUrl) {
                return $this->response->setJSON([
                    'status'  => 500,
                    'message' => 'Image generation failed.',
                    'data'    => $responseData,
                ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            return $this->response->setJSON([
                'status'  => 200,
                'message' => 'Image generated successfully.',
                'image_url' => $imageUrl,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'DALL·E API Error: ' . $e->getMessage());
            return $this->failServerError('Image generation error: ' . $e->getMessage());
        }
    }


    public function generateWithGemini()
    {
        $prompt = $this->request->getVar('prompt');
        $imageFile = $this->request->getFile('image'); // Optional

        if (empty($prompt)) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'Prompt is required.',
            ]);
        }

        try {
            $base64Image = null;
            $mimeType = null;

            if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
                $mimeType = $imageFile->getMimeType();
                $base64Image = base64_encode(file_get_contents($imageFile->getTempName()));
            }

            $geminiAI = new GeminiAI();
            $result = $geminiAI->generateImageWithOptionalImage($prompt, $base64Image, $mimeType);

            return $this->response->setJSON([
                'status'  => 200,
                'message' => 'Image generated successfully using Gemini.',
                'data'    => [
                    'prompt' => $prompt,
                    'generatedImages' => $result['images'] ?? [],
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Gemini API Error: ' . $e->getMessage());
            return $this->failServerError('Image generation error: ' . $e->getMessage());
        }
    }


    // public function edit()
    // {
    //     $prompt = $this->request->getPost('edit_prompt');
    //     $imageFile = $this->request->getFile('input_image');

    //     if (empty($prompt) || !$imageFile->isValid()) {
    //         return redirect()->back()->with('error', 'Please provide a prompt and an image for editing.');
    //     }

    //     try {
    //         // Read image and convert to base64
    //         $imageData = file_get_contents($imageFile->getTempName());
    //         $imageDataBase64 = base64_encode($imageData);
    //         $imageMimeType = $imageFile->getMimeType();

    //         $geminiAI = new GeminiAI();
    //         $result = $geminiAI->editImage($prompt, $imageDataBase64, $imageMimeType);

    //         $data = [
    //             'prompt' => $prompt,
    //             'editedImages' => $result['images'],
    //             'generatedText' => $result['text']
    //         ];

    //         return view('image_editor_result', $data);
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with('error', $e->getMessage());
    //     }
    // }
}
