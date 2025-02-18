<?php

namespace App\Controllers;

use App\Models\ServiceModel;
use CodeIgniter\RESTful\ResourceController;

class ServiceController extends ResourceController
{
    protected $serviceModel;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
    }

     // ✅ READ - Get all services
     public function index()
     {
         $services = $this->serviceModel->findAll();
         return $this->respond($services);
     }

      // ✅ READ - Get a single service by ID
    public function show($id = null)
    {
        $service = $this->serviceModel->find($id);
        if (!$service) {
            return $this->failNotFound('Service not found.');
        }
        return $this->respond($service);
    }
 

    // ✅ UPLOAD IMAGE SEPARATELY
    public function uploadImage()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'image' => 'uploaded[image]|is_image[image]|max_size[image,2048]|mime_in[image,image/png,image/jpeg,image/jpg]',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->fail($validation->getErrors());
        }

        $imageFile = $this->request->getFile('image');

        if ($imageFile->isValid() && !$imageFile->hasMoved()) {
            $newName = $imageFile->getRandomName();
            $imageFile->move('uploads/', $newName);
            $imagePath = '/uploads/' . $newName;

            return $this->respond(['message' => 'Image uploaded successfully', 'image_url' => $imagePath], 200);
        }

        return $this->fail('Image upload failed.');
    }

    // ✅ CREATE SERVICE (with separate image URL)
    public function create()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'name' => 'required|string|max_length[255]',
            'image'   => 'required|string|max_length[255]', // Expecting image URL
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->fail($validation->getErrors());
        }

        $data = [
            'name' => $this->request->getVar('name'),
            'image'   => $this->request->getVar('image'), // Image URL from upload
        ];

        if ($this->serviceModel->insert($data)) {
            return $this->respondCreated(['message' => 'Service added successfully', 'data' => $data]);
        }

        return $this->fail('Failed to add service.');
    }

    // ✅ UPDATE - Update a service with image path
public function update($id = null)
{
    $service = $this->serviceModel->find($id);
    if (!$service) {
        return $this->failNotFound('Service not found.');
    }

    $validation = \Config\Services::validation();
    $validation->setRules([
        'name' => 'required|string|max_length[255]',
        'image_url' => 'permit_empty|string|max_length[255]', // Accept only the image path
    ]);

    if (!$validation->withRequest($this->request)->run()) {
        return $this->fail($validation->getErrors());
    }

    $name = $this->request->getVar('name');
    $imagePath = $this->request->getVar('image_url') ?: $service['image']; // Keep existing image if not provided

    $data = [
        'name' => $name,
        'image'   => $imagePath,
    ];

    if ($this->serviceModel->update($id, $data)) {
        return $this->respondUpdated([
            'message' => 'Service updated successfully',
            'data' => $data
        ]);
    }

    return $this->fail('Failed to update service.');
}

}
