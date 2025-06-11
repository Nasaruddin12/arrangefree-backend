<?php

namespace App\Controllers;

use App\Models\StylesCategoryModel;
use CodeIgniter\RESTful\ResourceController;

class StylesCategoryController extends ResourceController
{
    protected $model;

    public function __construct()
    {
        $this->model = new StylesCategoryModel();
        helper(['form', 'url']);
    }

    // GET /styles-category
    public function index()
    {
        return $this->response->setJSON($this->model->findAll());
    }

    // POST /styles-category/create
    public function create()
    {
        $rules = [
            'name'  => 'required',
            'image' => 'if_exist|is_image[image]|max_size[image,2048]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => $this->validator->getErrors()
            ]);
        }

        $imagePath = null;
        $imageFile = $this->request->getFile('image');

        if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
            $newName  = $imageFile->getRandomName();
            $savePath = 'public/uploads/styles-category/';
            $imageFile->move(FCPATH . $savePath, $newName);
            $imagePath = $savePath . $newName;
        }

        $this->model->save([
            'name'   => $this->request->getVar('name'),
            'image'  => $imagePath,
            'status' => 'active',
        ]);

        return $this->response->setJSON(['message' => 'Style category created successfully']);
    }

    // GET /styles-category/show/{id}
    public function show($id = null)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Not found']);
        }
        return $this->response->setJSON($data);
    }

    // POST /styles-category/update/{id}
    public function update($id = null)
    {
        if (!$this->model->find($id)) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Not found']);
        }

        $update = [];

        $name = $this->request->getVar('name');
        $status = $this->request->getVar('status');

        if (!empty($name)) {
            $update['name'] = $name;
        }

        if (!empty($status)) {
            $update['status'] = $status;
        }

        $this->model->update($id, $update);

        return $this->response->setJSON(['message' => 'Updated successfully']);
    }

    // GET /styles-category/delete/{id}
    public function delete($id = null)
    {
        if (!$this->model->find($id)) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Not found']);
        }

        $this->model->delete($id);

        return $this->response->setJSON(['message' => 'Deleted successfully']);
    }
}
