<?php

namespace App\Controllers;

use App\Models\FreepikApiHistoryModel;
use CodeIgniter\RESTful\ResourceController;
use Exception;

class FreepikApiHistoryController extends ResourceController
{


    public function store()
    {
        try {
            $user_id = $this->request->getVar('user_id');
            $prompt  = $this->request->getVar('prompt');
            $images  = $this->request->getFiles(); // Multiple images
            $uploadDirectory = 'uploads/freepik-api-history/';
            $imagePaths = [];

            if (empty($user_id) || empty($prompt)) {
                return $this->respond(['status' => 400, 'message' => 'User ID and prompt are required'], 400);
            }

            if (!isset($images['images'])) {
                return $this->respond(['status' => 400, 'message' => 'No images uploaded'], 400);
            }

            foreach ($images['images'] as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $fileName = $file->getRandomName();
                    if ($file->move($uploadDirectory, $fileName)) {
                        $imagePaths[] = $uploadDirectory . $fileName;
                    }
                }
            }

            $model = new FreepikApiHistoryModel();
            $model->insert([
                'user_id'    => $user_id,
                'prompt'     => $prompt,
                'images'     => json_encode($imagePaths),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->respond([
                'status'  => 201,
                'message' => 'Data stored successfully',
                'data'    => ['images' => $imagePaths]
            ], 201);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Failed to store data', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAll()
    {
        try {
            $db = \Config\Database::connect(); // Connect to database
            $model = new FreepikApiHistoryModel();

            // Get query parameters
            $page = (int) ($this->request->getVar('page') ?? 1);
            $perPage = (int) ($this->request->getVar('perPage') ?? 10);
            $search = $this->request->getVar('search');
            $startDate = $this->request->getVar('start_date');
            $endDate = $this->request->getVar('end_date');
            $offset = max(0, ($page - 1) * $perPage);

            // Subquery to get latest usage per customer
            $subQuery = $db->table('freepik_api_history')
                ->select('user_id, MAX(created_at) as latest_usage')
                ->groupBy('user_id')
                ->getCompiledSelect();

            // Main query: Get customers with their latest usage
            $query = $db->table('freepik_api_history')
                ->select('af_customers.id AS user_id, af_customers.name, recent_usage.latest_usage AS created_at, COUNT(freepik_api_history.id) AS usage_count')
                ->join("($subQuery) AS recent_usage", 'recent_usage.user_id = freepik_api_history.user_id', 'inner')
                ->join('af_customers', 'af_customers.id = freepik_api_history.user_id', 'inner')
                ->groupBy('freepik_api_history.user_id')
                ->orderBy('recent_usage.latest_usage', 'DESC'); // Order by most recent usage

            // Apply search filter
            if (!empty($search)) {
                $query->like('af_customers.name', $search);
            }

            // Apply date range filter
            if (!empty($startDate) && !empty($endDate)) {
                $query->where('freepik_api_history.created_at >=', date('Y-m-d', strtotime($startDate)))
                    ->where('freepik_api_history.created_at <=', date('Y-m-d', strtotime($endDate)));
            }

            // Get total unique customer count
            $totalRecordsQuery = clone $query;
            $totalRecords = count($totalRecordsQuery->get()->getResultArray());

            // Apply pagination
            $data = $query->limit($perPage, $offset)->get()->getResultArray();

            // Check if data exists
            if (empty($data)) {
                return $this->failNotFound('No customers found who used the Freepik API.');
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




    public function getByUser($user_id)
    {
        $model = new FreepikApiHistoryModel();
        $data = $model->where('user_id', $user_id)
            ->where('type', 'search')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return $this->respond(['status' => 200, 'data' => $data], 200);
    }
    public function getByUserAll($user_id)
    {
        $model = new FreepikApiHistoryModel();
        $data = $model->where('user_id', $user_id)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return $this->respond(['status' => 200, 'data' => $data], 200);
    }
    public function checkUserLimit($user_id)
    {
        $model = new FreepikApiHistoryModel();
        $requestCount = $model->where('user_id', $user_id)->countAllResults();

        if ($requestCount <= 250) {
            return $this->respond(['status' => 200, 'allowed' => true, 'count' => $requestCount,], 200);
        } else {
            return $this->respond([
                'status' => 403,
                'allowed' => false,
                'message' => 'You have exceeded the limit. Reach out to Seeb Design.'
            ], 403);
        }
    }

    public function imageGenerate()
    {
        try {
            $user_id = $this->request->getVar('user_id');
            $prompt  = $this->request->getVar('prompt');
            $type  = $this->request->getVar('type');

            if (!in_array($type, ['search', 'floorplan'])) {
                return $this->respond([
                    'status'  => 422,
                    'message' => 'Invalid type. Allowed values: search, floorplan.'
                ], 422);
            }

            if (empty($user_id) || empty($prompt)) {
                return $this->respond(['status' => 400, 'message' => 'User ID and prompt are required'], 400);
            }

            $prompt  = $this->request->getVar('prompt');

            // $originalPrompt = $prompt;
            $prompt = strtolower($prompt);

            // Interior design keyword filter
            $interiorKeywords = [
                // 🔹 General Concepts
                'interior',
                'interior design',
                'home decor',
                'space planning',
                'room layout',
                '3d rendering',
                'interior visualization',
                'home layout',
                'floor plan',
                'elevation design',
                'house interior',

                // 🔹 Rooms & Zones
                'living room',
                'bedroom',
                'master bedroom',
                'kids room',
                'guest bedroom',
                'study room',
                'home office',
                'kitchen',
                'modular kitchen',
                'dining area',
                'bathroom',
                'toilet',
                'foyer',
                'entryway',
                'balcony',
                'terrace',
                'utility room',
                'laundry room',
                'puja room',
                'mandir',
                'garage',
                'basement',

                // 🔹 Furniture & Fixtures
                'sofa',
                'bed',
                'wardrobe',
                'tv unit',
                'bookshelf',
                'dining table',
                'side table',
                'center table',
                'coffee table',
                'shoe rack',
                'vanity',
                'dressing table',
                'study table',
                'cabinets',
                'modular units',
                'floating shelves',
                'bar unit',
                'console table',
                'bench',
                'ottoman',
                'recliner',
                'swing chair',
                'chair',

                // 🔹 Ceilings & Walls
                'false ceiling',
                'pop ceiling',
                'gypsum ceiling',
                'wooden ceiling',
                'stretch ceiling',
                'wall panel',
                'accent wall',
                'feature wall',
                'brick wall',
                'wood cladding',
                'stone cladding',
                'wallpaper',
                'textured paint',
                '3d panels',
                'mirror wall',
                'fabric panel',

                // 🔹 Flooring
                'wooden flooring',
                'marble flooring',
                'tiles',
                'vinyl flooring',
                'granite flooring',
                'herringbone pattern',
                'matte finish floor',
                'anti-skid tiles',
                'cement finish',

                // 🔹 Lighting
                'ambient lighting',
                'task lighting',
                'cove lighting',
                'spotlight',
                'strip light',
                'false ceiling light',
                'pendant light',
                'chandelier',
                'floor lamp',
                'table lamp',
                'wall sconce',
                'smart lighting',

                // 🔹 Materials & Finishes
                'laminate',
                'veneer',
                'acrylic',
                'glass',
                'mirror',
                'metal',
                'duco',
                'mica',
                'pvc panels',
                'soft close drawers',
                'granite',
                'marble',
                'quartz',
                'corian',
                'terrazzo',
                'cement texture',

                // 🔹 Themes & Styles
                'modern',
                'contemporary',
                'luxury',
                'minimalist',
                'rustic',
                'scandinavian',
                'traditional',
                'ethnic',
                'fusion',
                'bohemian',
                'industrial',
                'art deco',
                'vintage',
                'royal theme',
                'zen style',

                // 🔹 Colors & Styling
                'color palette',
                'neutral tones',
                'earthy tones',
                'warm lighting',
                'pastel shades',
                'accent color',
                'bold interiors',
                'matte finish',
                'glossy finish',

                // 🔹 Decor & Accessories
                'curtains',
                'window blinds',
                'carpet',
                'rugs',
                'planters',
                'indoor plants',
                'mirror decor',
                'wall art',
                'photo frame wall',
                'clocks',
                'sculpture',
                'vases',
                'showcase',

                // 🔹 Functionality
                'space saving',
                'foldable furniture',
                'multi-functional furniture',
                'hidden storage',
                'pull-out unit',
                'corner storage',
                'storage bed',
                'modular storage',
                'smart furniture',

                // 🔹 Home Technology
                'home automation',
                'smart home',
                'voice control lighting',
                'automated curtains',
                'smart thermostat',
                'digital door lock',
                'video door phone',
                'motion sensor lights',

                // 🔹 Regional Elements (India-specific)
                'jali partition',
                'jaali design',
                'mandala art',
                'rangoli area',
                'puja mandir',
                'indian style interiors',
                'south indian wood decor',
                'rajasthani interior',
                'mughal theme',
                'ethnic cushion covers',

                // 🔹 Extras
                'interior theme',
                'visual moodboard',
                'lighting layout',
                'furniture layout',
                'design mockup',
                'virtual interior',
                'ai interior design',
                'interior mockup',
                'home vibe',
                'spatial harmony'
            ];

            $prompt = strtolower($prompt);
            $interiorKeywords = array_map('strtolower', $interiorKeywords);

            $isInteriorRelated = false;
            foreach ($interiorKeywords as $keyword) {
                if (strpos($prompt, $keyword) !== false) {
                    $isInteriorRelated = true;
                    break;
                }
            }

            if (!$isInteriorRelated) {
                $prompt = 'Interior design of a modern living room';
            }


            // 🔐 Check user image generation limit
            $model = new \App\Models\FreepikApiHistoryModel();
            $requestCount = $model->where('user_id', $user_id)->countAllResults();

            if ($requestCount >= 250) {
                return $this->respond([
                    'status' => 403,
                    'allowed' => false,
                    'message' => 'You have exceeded the limit. Reach out to Seeb Design.'
                ], 403);
            }

            // 🔁 Call Freepik API
            $response = $this->callFreepikApi($prompt);
            if (!$response || empty($response['data'])) {
                return $this->respond(['status' => 400, 'message' => 'No images generated'], 400);
            }

            $uploadDirectory = 'public/uploads/freepik-api-history/';
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0777, true);
            }

            $imagePaths = [];

            foreach ($response['data'] as $index => $img) {
                $fileName = uniqid("img_") . '.png';
                $filePath = $uploadDirectory . $fileName;
                file_put_contents($filePath, base64_decode($img['base64']));
                $imagePaths[] = $uploadDirectory . $fileName;
            }

            // ✅ Save to DB
            $dataToInsert = [
                'user_id'    => $user_id,
                'prompt'     => $prompt,
                'images'     => json_encode($imagePaths),
                'type'       => $type,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if (!$model->insert($dataToInsert)) {
                return $this->respond([
                    'status'  => 400,
                    'message' => 'Insert failed',
                    'errors'  => $model->errors(),
                ], 400);
            }

            return $this->respond([
                'status'  => 201,
                'message' => 'Images generated and saved successfully',
                'data'    => ['images' => $imagePaths]
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => 'Failed to generate images',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    private function callFreepikApi(string $prompt): ?array
    {
        $client = \Config\Services::curlrequest();

        $payload = [
            'prompt' => $prompt,
            'negative_prompt' => 'b&w, earth, cartoon, ugly',
            // 'guidance_scale' => 2,
            // 'seed' => 42,
            'num_images' => 2,
            'image' => ['size' => 'square_1_1'],
            // 'styling' => [
            //     'style' => 'anime',
            //     'effects' => [
            //         'color' => 'pastel',
            //         'lightning' => 'warm',
            //         'framing' => 'portrait'
            //     ],
            //     'colors' => [
            //         ['color' => '#FF5733', 'weight' => 1],
            //         ['color' => '#33FF57', 'weight' => 1],
            //     ]
            // ],
            // 'filter_nsfw' => true
        ];

        $headers = [
            'Content-Type'        => 'application/json',
            'x-freepik-api-key'   => 'FPSX6fb14b5c917c4ba5a9f150a5184bc728'
            // 'x-freepik-api-key'   => 'FPSX957aabc7c6bdddd5244c17564b0b6826'
        ];

        $response = $client->post(
            'https://api.freepik.com/v1/ai/text-to-image',
            ['headers' => $headers, 'json' => $payload]
        );

        return json_decode($response->getBody(), true);
    }
}
