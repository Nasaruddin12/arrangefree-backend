<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ServiceGalleryModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class ServiceGalleryController extends BaseController
{
    use ResponseTrait;
    protected $serviceGalleryModel;

    public function __construct()
    {
        $this->serviceGalleryModel = new ServiceGalleryModel();
    }

    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            // Validate input
            $validation = \Config\Services::validation();
            $validation->setRules([
                'service_id' => 'required|integer',
                'media_type' => 'required|in_list[image,video,tutorial]',
                'title' => 'permit_empty|string|max_length[255]',
                'description' => 'permit_empty|string',
                'media_url' => 'required|string',
                'thumbnail_url' => 'permit_empty|string',
                'sort_order' => 'permit_empty|integer',
                'is_active' => 'permit_empty|in_list[0,1]',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ], 400);
            }

            // Insert data
            if (!$this->serviceGalleryModel->insert($data)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Failed to create service gallery item',
                    'errors' => $this->serviceGalleryModel->errors()
                ], 400);
            }

            $id = $this->serviceGalleryModel->insertID();

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Service gallery item created successfully',
                'data' => $this->serviceGalleryModel->find($id)
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceGallery Create Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function list()
    {
        try {
            $serviceId = $this->request->getVar('service_id');
            $mediaType = $this->request->getVar('media_type');
            $isActive = $this->request->getVar('is_active');

            $builder = $this->serviceGalleryModel;

            if ($serviceId) {
                $builder->where('service_id', $serviceId);
            }

            if ($mediaType) {
                $builder->where('media_type', $mediaType);
            }

            if ($isActive !== null) {
                $builder->where('is_active', $isActive);
            }

            $items = $builder->orderBy('sort_order', 'ASC')->findAll();

            return $this->respond([
                'status' => 200,
                'message' => 'Service gallery items retrieved successfully',
                'data' => $items
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceGallery List Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function getById($id)
    {
        try {
            $item = $this->serviceGalleryModel->find($id);

            if (!$item) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Service gallery item not found'
                ], 404);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Service gallery item retrieved successfully',
                'data' => $item
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceGallery GetById Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function update($id)
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getVar();

            // Check if item exists
            $item = $this->serviceGalleryModel->find($id);
            if (!$item) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Service gallery item not found'
                ], 404);
            }

            // Validate input
            $validation = \Config\Services::validation();
            $validation->setRules([
                'service_id' => 'permit_empty|integer',
                'media_type' => 'permit_empty|in_list[image,video,tutorial]',
                'title' => 'permit_empty|string|max_length[255]',
                'description' => 'permit_empty|string',
                'media_url' => 'permit_empty|string',
                'thumbnail_url' => 'permit_empty|string',
                'sort_order' => 'permit_empty|integer',
                'is_active' => 'permit_empty|in_list[0,1]',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ], 400);
            }

            // Update data
            if (!$this->serviceGalleryModel->update($id, $data)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Failed to update service gallery item',
                    'errors' => $this->serviceGalleryModel->errors()
                ], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Service gallery item updated successfully',
                'data' => $this->serviceGalleryModel->find($id)
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceGallery Update Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $item = $this->serviceGalleryModel->find($id);
            if (!$item) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Service gallery item not found'
                ], 404);
            }

            if (!$this->serviceGalleryModel->delete($id)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Failed to delete service gallery item'
                ], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Service gallery item deleted successfully'
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceGallery Delete Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    // Upload Images for service gallery
    public function uploadImages()
    {
        try {
            $imageFiles = $this->request->getFiles();
            $serviceId = $this->request->getVar('service_id');
            $title = $this->request->getVar('title') ?? null;
            $description = $this->request->getVar('description') ?? null;
            $sortOrder = $this->request->getVar('sort_order') ?? 0;

            if (!$serviceId) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Service ID is required'
                ], 400);
            }

            $uploadedItems = [];

            if (!empty($imageFiles['images'])) {
                foreach ($imageFiles['images'] as $imageFile) {
                    if ($imageFile->isValid() && !$imageFile->hasMoved()) {
                        // Validate mime type for images
                        $allowedTypes = [
                            'image/png',
                            'image/jpeg',
                            'image/jpg',
                            'image/gif',
                            'image/webp'
                        ];

                        if (!in_array($imageFile->getMimeType(), $allowedTypes)) {
                            continue; // skip this file
                        }

                        // Move valid image
                        $newName = $imageFile->getRandomName();
                        $imageFile->move('public/uploads/service-gallery/', $newName);
                        $imagePath = 'public/uploads/service-gallery/' . $newName;

                        // Create gallery item
                        $galleryData = [
                            'service_id' => $serviceId,
                            'media_type' => 'image',
                            'title' => $title,
                            'description' => $description,
                            'media_url' => $imagePath,
                            'sort_order' => $sortOrder,
                            'is_active' => 1
                        ];

                        if ($this->serviceGalleryModel->insert($galleryData)) {
                            $uploadedItems[] = $this->serviceGalleryModel->find($this->serviceGalleryModel->insertID());
                        }
                    }
                }
            }

            if (empty($uploadedItems)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'No valid images were uploaded'
                ], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Images uploaded and gallery items created successfully',
                'data' => $uploadedItems
            ], 200);
        } catch (Exception $e) {
            log_message('error', 'ServiceGallery UploadImages Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Image upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Add Video for service gallery
    public function addVideo()
    {
        try {
            $videoFiles = $this->request->getFiles();
            $serviceId = $this->request->getVar('service_id');
            $title = $this->request->getVar('title') ?? null;
            $description = $this->request->getVar('description') ?? null;
            $sortOrder = $this->request->getVar('sort_order') ?? 0;
            $thumbnailUrl = $this->request->getVar('thumbnail_url') ?? null;
            $mediaUrl = $this->request->getVar('media_url') ?? null; // For YouTube links

            if (!$serviceId) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Service ID is required'
                ], 400);
            }

            $uploadedItems = [];

            // Check if YouTube URL is provided
            if (!empty($mediaUrl)) {
                // Validate YouTube URL
                if (!$this->isValidYouTubeUrl($mediaUrl)) {
                    return $this->respond([
                        'status' => 400,
                        'message' => 'Invalid YouTube URL format'
                    ], 400);
                }

                // Create gallery item with YouTube URL
                $galleryData = [
                    'service_id' => $serviceId,
                    'media_type' => 'video',
                    'title' => $title,
                    'description' => $description,
                    'media_url' => $mediaUrl,
                    'thumbnail_url' => $thumbnailUrl,
                    'sort_order' => $sortOrder,
                    'is_active' => 1
                ];

                if ($this->serviceGalleryModel->insert($galleryData)) {
                    $uploadedItems[] = $this->serviceGalleryModel->find($this->serviceGalleryModel->insertID());
                }
            }
            // Check if video file is uploaded
            elseif (!empty($videoFiles['video'])) {
                $videoFile = $videoFiles['video'];

                if ($videoFile->isValid() && !$videoFile->hasMoved()) {
                    // Validate mime type for videos
                    $allowedTypes = [
                        'video/mp4',
                        'video/avi',
                        'video/mov',
                        'video/quicktime',
                        'video/x-msvideo',
                        'video/webm'
                    ];

                    if (!in_array($videoFile->getMimeType(), $allowedTypes)) {
                        return $this->respond([
                            'status' => 400,
                            'message' => 'Invalid video file type'
                        ], 400);
                    }

                    // Move valid video
                    $newName = $videoFile->getRandomName();
                    $videoFile->move('public/uploads/service-gallery/', $newName);
                    $videoPath = 'public/uploads/service-gallery/' . $newName;

                    // Create gallery item
                    $galleryData = [
                        'service_id' => $serviceId,
                        'media_type' => 'video',
                        'title' => $title,
                        'description' => $description,
                        'media_url' => $videoPath,
                        'thumbnail_url' => $thumbnailUrl,
                        'sort_order' => $sortOrder,
                        'is_active' => 1
                    ];

                    if ($this->serviceGalleryModel->insert($galleryData)) {
                        $uploadedItems[] = $this->serviceGalleryModel->find($this->serviceGalleryModel->insertID());
                    }
                }
            } else {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Either video file or media_url (YouTube link) is required'
                ], 400);
            }

            if (empty($uploadedItems)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Failed to add video'
                ], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Video added to gallery successfully',
                'data' => $uploadedItems
            ], 200);
        } catch (Exception $e) {
            log_message('error', 'ServiceGallery AddVideo Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Video addition failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Add Tutorial Video for service gallery
    public function addTutorialVideo()
    {
        try {
            $videoFiles = $this->request->getFiles();
            $serviceId = $this->request->getVar('service_id');
            $title = $this->request->getVar('title') ?? null;
            $description = $this->request->getVar('description') ?? null;
            $sortOrder = $this->request->getVar('sort_order') ?? 0;
            $thumbnailUrl = $this->request->getVar('thumbnail_url') ?? null;
            $mediaUrl = $this->request->getVar('media_url') ?? null; // For YouTube links

            if (!$serviceId) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Service ID is required'
                ], 400);
            }

            $uploadedItems = [];

            // Check if YouTube URL is provided
            if (!empty($mediaUrl)) {
                // Validate YouTube URL
                if (!$this->isValidYouTubeUrl($mediaUrl)) {
                    return $this->respond([
                        'status' => 400,
                        'message' => 'Invalid YouTube URL format'
                    ], 400);
                }

                // Create gallery item with YouTube URL
                $galleryData = [
                    'service_id' => $serviceId,
                    'media_type' => 'tutorial',
                    'title' => $title,
                    'description' => $description,
                    'media_url' => $mediaUrl,
                    'thumbnail_url' => $thumbnailUrl,
                    'sort_order' => $sortOrder,
                    'is_active' => 1
                ];

                if ($this->serviceGalleryModel->insert($galleryData)) {
                    $uploadedItems[] = $this->serviceGalleryModel->find($this->serviceGalleryModel->insertID());
                }
            }
            // Check if tutorial video file is uploaded
            elseif (!empty($videoFiles['tutorial_video'])) {
                $videoFile = $videoFiles['tutorial_video'];

                if ($videoFile->isValid() && !$videoFile->hasMoved()) {
                    // Validate mime type for tutorial videos
                    $allowedTypes = [
                        'video/mp4',
                        'video/avi',
                        'video/mov',
                        'video/quicktime',
                        'video/x-msvideo',
                        'video/webm'
                    ];

                    if (!in_array($videoFile->getMimeType(), $allowedTypes)) {
                        return $this->respond([
                            'status' => 400,
                            'message' => 'Invalid tutorial video file type'
                        ], 400);
                    }

                    // Move valid tutorial video
                    $newName = $videoFile->getRandomName();
                    $videoFile->move('public/uploads/service-gallery/', $newName);
                    $videoPath = 'public/uploads/service-gallery/' . $newName;

                    // Create gallery item
                    $galleryData = [
                        'service_id' => $serviceId,
                        'media_type' => 'tutorial',
                        'title' => $title,
                        'description' => $description,
                        'media_url' => $videoPath,
                        'thumbnail_url' => $thumbnailUrl,
                        'sort_order' => $sortOrder,
                        'is_active' => 1
                    ];

                    if ($this->serviceGalleryModel->insert($galleryData)) {
                        $uploadedItems[] = $this->serviceGalleryModel->find($this->serviceGalleryModel->insertID());
                    }
                }
            } else {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Either tutorial_video file or media_url (YouTube link) is required'
                ], 400);
            }

            if (empty($uploadedItems)) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Failed to add tutorial video'
                ], 400);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Tutorial video added to gallery successfully',
                'data' => $uploadedItems
            ], 200);
        } catch (Exception $e) {
            log_message('error', 'ServiceGallery AddTutorialVideo Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Tutorial video addition failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Helper method to validate YouTube URLs
    private function isValidYouTubeUrl($url)
    {
        $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        return preg_match($pattern, $url);
    }

    // Get services with gallery statistics
    public function getServicesWithGalleryStats()
    {
        try {
            // Get all services
            $services = $this->serviceGalleryModel->db
                ->table('services')
                ->select('services.id, services.name, services.image as service_image')
                ->where('services.status', 1) // Only active services
                ->get()
                ->getResultArray();

            // Get gallery statistics for each service
            foreach ($services as &$service) {
                $stats = $this->serviceGalleryModel->db
                    ->table('service_gallery')
                    ->select('media_type, COUNT(*) as count')
                    ->where('service_id', $service['id'])
                    ->where('is_active', 1)
                    ->groupBy('media_type')
                    ->get()
                    ->getResultArray();

                // Initialize counts
                $service['image_count'] = 0;
                $service['video_count'] = 0;
                $service['tutorial_count'] = 0;

                // Fill in the counts
                foreach ($stats as $stat) {
                    switch ($stat['media_type']) {
                        case 'image':
                            $service['image_count'] = (int) $stat['count'];
                            break;
                        case 'video':
                            $service['video_count'] = (int) $stat['count'];
                            break;
                        case 'tutorial':
                            $service['tutorial_count'] = (int) $stat['count'];
                            break;
                    }
                }

                // Calculate total gallery items
                $service['total_gallery_items'] = $service['image_count'] + $service['video_count'] + $service['tutorial_count'];
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Services with gallery statistics retrieved successfully',
                'data' => $services
            ]);
        } catch (Exception $e) {
            log_message('error', 'ServiceGallery GetServicesWithGalleryStats Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 500,
                'message' => 'Something went wrong'
            ], 500);
        }
    }
}