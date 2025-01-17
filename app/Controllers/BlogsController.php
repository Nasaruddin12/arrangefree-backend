<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BlogsModel;
use App\Models\BlogsSectionModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class BlogsController extends BaseController
{
    use ResponseTrait;
    public function createBlog()
    {
        try {
            $blogsmodel = new BlogsModel();
            $blogsSectionModel = new BlogsSectionModel();
            $validation = \Config\Services::validation();
            $statusCode = 200;
            // print_r($this->request->getVar());die;
            $id = $this->request->getVar('id');
            $title = $this->request->getVar('title');
            $description = $this->request->getVar('description');
            $blog_image = $this->request->getVar('blog_image');
            $status = $this->request->getVar('status');
            // echo $title; die;
            if ($id == null) {
                $validation = &$blogsmodel;
                $blogsmodel->insert([
                    'title' => $title,
                    'description' => $description,
                    'blog_image' => $blog_image,
                    'status' => $status
                ]);
                // echo $blogsmodel->db->getLastQuery();
                if (!empty($validation->errors())) {
                    throw new Exception('Validation', 400);
                }
                if ($validation->db->error()['code']) {
                    throw new Exception($validation->db->error()['message'], 500);
                }
                $id = $blogsmodel->db->insertID();
            } else {
                $validation = &$blogsmodel;
                $blogsmodel->set([
                    'title' => $title,
                    'description' => $description,
                    'blog_image' => $blog_image,
                    'status' => $status
                ])->update($id);
                if (!empty($validation->errors())) {
                    throw new Exception('Validation', 400);
                }
                if ($validation->db->error()['code']) {
                    throw new Exception($validation->db->error()['message'], 500);
                }
            }

            $sectionData = $this->request->getVar('section_content');
            // var_dump($sectionData);
            $sectionData = json_decode(json_encode($sectionData), true);
            // print_r($sectionData);
            // die;
            if (!empty($sectionData)) {
                foreach ($sectionData as $key => &$sectionItem) {
                    if (!isset($sectionItem['id'])) {
                        // echo "yes:";
                        $sectionItem['blog_id'] = $id;
                        $validation = &$blogsSectionModel;
                        $blogsSectionModel->insert($sectionItem);
                        if (!empty($validation->errors())) {
                            throw new Exception('Validation', 400);
                        }
                        if ($validation->db->error()['code']) {
                            throw new Exception($validation->db->error()['message'], 500);
                        }
                        $sectionItem['id'] = $blogsSectionModel->db->insertID();
                    } else {
                        $validation = &$blogsSectionModel;
                        $blogsSectionModel->set($sectionItem)->update($sectionItem['id']);
                        if (!empty($validation->errors())) {
                            throw new Exception('Validation', 400);
                        }
                        if ($validation->db->error()['code']) {
                            throw new Exception($validation->db->error()['message'], 500);
                        }
                    }
                }
            }
            $statusCode = 200;
            $response = [
                'message' => 'Blog Created Successfully.',
                'data' => [
                    'blog_id' => $id,
                    'section_data' => $sectionData,
                ],
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getCode() === 400 ? ['validation' => $validation->getErrors()] : $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function createBlogImage()
    {
        $image = \Config\Services::image();
        try {
            $blogsmodel = new Blogsmodel();
            $validation = &$blogsmodel;
            $statusCode = 200;


            $productImage = $this->request->getFile('blog_image');
            $blog = "public/uploads/blogs/";
            $imageName = bin2hex(random_bytes(10)) . time() . '.jpeg';
            $productImagesData = array();
            // $productID = $this->request->getVar('product_id');
            $image->withFile($productImage)
                // ->resize(1080, 1620, true)
                ->resize(1620, 1620, true)
                ->convert(IMAGETYPE_JPEG)
                ->save($blog . $imageName, 90);

            $data = [
                'blog_image' => $blog . $imageName,
            ];

            $response = [
                'message' => 'Blog Image created successfully.',
                'data' => $data,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function deleteBlogImage()
    {
        try {
            $blogID = $this->request->getVar('id');
            $image_path = $this->request->getVar('blog_image');
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            if (isset($blogID)) {
                $blogModel = new BlogsModel();
                $blogModel->set(['blog_image' => ""])->update($blogID);
            }

            $statusCode = 200;
            $response = [
                'message' => 'Blog Image deleted successfully.'
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function deleteSectionImage()
    {
        try {
            $sectionID = $this->request->getVar('id');
            $image_path = $this->request->getVar('section_image');
            if (isset($sectionID)) {
                $blogsSectionModel = new BlogsSectionModel();
                $blogsSectionModel->set(['banner_image' => ''])->update($sectionID);
            }
            if (file_exists($image_path)) {
                unlink($image_path);
            }

            $statusCode = 200;
            $response = [
                'message' => 'Section Image deleted successfully.'
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
        }
        $response['status'] = $statusCode;;
        return $this->respond($response, $statusCode);
    }

    public function deleteBlog($id)
    {
        try {
            $BlogModel = new BlogsModel();
            $BlogsSectionModel = new BlogsSectionModel();

            // Retrieve the blog entry to get the image path and other details
            $blogData = $BlogModel->find($id);
            if (empty($blogData)) {
                throw new Exception('Blog not found', 404);
            }

            // Retrieve and delete the associated blog sections
            $blogSections = $BlogsSectionModel->where('blog_id', $id)->findAll();
            if (!empty($blogSections)) {
                foreach ($blogSections as $section) {
                    // Delete any images associated with the blog section (if applicable)
                    $sectionImagePath = $section['banner_image']; // Assuming each section might have an image
                    if ($sectionImagePath && file_exists($sectionImagePath)) {
                        unlink($sectionImagePath); // Delete the section image
                    }

                    // Delete the blog section
                    $BlogsSectionModel->delete($section['id']);
                }
            }

            // Now delete the blog image if it exists
            $image_path = $blogData['blog_image']; // Assuming 'blog_image' stores the image path
            if ($image_path && file_exists($image_path)) {
                unlink($image_path); // Delete the blog image from the server
            }

            // Finally, delete the blog itself
            $BlogModel->delete($id);

            // Check if the deletion was successful
            if ($BlogModel->db->affectedRows() == 1) {
                $statusCode = 200;
                $response = [
                    'message' => 'Blog and associated sections deleted successfully.'
                ];
            } else {
                throw new Exception('Nothing to delete', 200);
            }
        } catch (Exception $e) {
            // Handle error and return appropriate status code
            $statusCode = $e->getCode() === 400 ? 400 : ($e->getCode());
            $response = [
                'error' => $e->getMessage()
            ];
        }

        // Set response status
        $response['status'] = $statusCode;

        // Return the response
        return $this->respond($response, $statusCode);
    }

    public function getPublicBlogs()
    {
        try {
            $blogsModel = new BlogsModel();

            // Retrieve blogs with status 1
            $blogsData = $blogsModel
                ->select(['id', 'title', 'description', 'blog_image', 'status'])
                ->where('status', 1)
                ->findAll();

            $statusCode = 200;
            $response = [
                'data' => $blogsData,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }


    public function singleBlog($id = 0)
    {
        try {
            $blogsModel = new BlogsModel();
            $blogsSectionModel = new BlogsSectionModel();
            $blogData = $blogsModel->find($id);
            if (!empty($blogData)) {
                $blogData['blog_sections'] = $blogsSectionModel->where('blog_id', $id)->findAll();
            }

            $statusCode = 200;
            $response = [
                'data' => $blogData,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    public function createBlogSection()
    {
        try {
            $BlogSectionModel = new BlogsSectionModel();

            $data = [
                'blog_id' => $this->request->getVar('blog_id'),
                'title' => $this->request->getVar('title'),
                'description' => $this->request->getVar('description'),
                'banner_image' => $this->request->getVar('banner_image'), // Image path or filename
                'section_link' => $this->request->getVar('section_link'),
            ];

            $BlogSectionModel->insert($data);

            $statusCode = 201;
            $response = [
                'message' => 'Blog section created successfully.',
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    public function deleteBlogSection($id)
    {
        try {
            $BlogSectionModel = new BlogsSectionModel();

            // Find the section by ID
            $section = $BlogSectionModel->find($id);

            // If section doesn't exist, throw an exception
            if (!$section) {
                throw new Exception('Blog section not found', 404);
            }

            // Retrieve and delete associated banner image if it exists
            $image_path = $section['banner_image'];
            if ($image_path && file_exists($image_path)) {
                unlink($image_path); // Delete image
            }

            // Delete the blog section
            $BlogSectionModel->delete($id);

            // Set response
            $statusCode = 200;
            $response = [
                'message' => 'Blog section and banner image deleted successfully.'
            ];
        } catch (Exception $e) {
            // Handle errors
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        // Add status code to the response and return it
        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    public function getBlogSections($id = null)
    {
        try {
            $BlogSectionModel = new BlogsSectionModel();

            // Check if an ID is provided in the URL
            if ($id) {
                // Fetch a specific section by ID
                $section = $BlogSectionModel->find($id);

                if (!$section) {
                    throw new Exception('Blog section not found.');
                }

                $statusCode = 200;
                $response = [
                    'message' => 'Blog section retrieved successfully.',
                    'data' => $section,
                ];
            } else {
                // Fetch all sections
                $sections = $BlogSectionModel->findAll();

                $statusCode = 200;
                $response = [
                    'message' => 'Blog sections retrieved successfully.',
                    'data' => $sections,
                ];
            }
        } catch (Exception $e) {
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage(),
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    public function updateBlogSection($id)
    {
        try {
            $BlogSectionModel = new BlogsSectionModel();

            $data = [
                'title' => $this->request->getVar('title'),
                'description' => $this->request->getVar('description'),
                'banner_image' => $this->request->getVar('banner_image'),
                'section_link' => $this->request->getVar('section_link'),
            ];

            $BlogSectionModel->update($id, $data);

            $statusCode = 200;
            $response = [
                'message' => 'Blog section updated successfully.',
                'data' => $data
            ];
        } catch (Exception $e) {
            $statusCode = 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }

    public function updateBlogStatus($id)
    {
        try {
            $blogsModel = new BlogsModel();

            // Get the input (new status) from the request
            $newStatus = $this->request->getVar('status');

            // Check if the blog exists
            $blog = $blogsModel->find($id);

            if (!$blog) {
                throw new Exception('Blog not found', 404);
            }

            // Update the status
            $blogsModel->update($id, ['status' => $newStatus]);

            // Prepare success response
            $statusCode = 200;
            $response = [
                'message' => 'Blog status updated successfully',
            ];
        } catch (Exception $e) {
            // Handle errors
            $statusCode = $e->getCode() === 404 ? 404 : 500;
            $response = [
                'error' => $e->getMessage(),
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    public function getAllBlogs()
    {
        try {
            $blogsModel = new BlogsModel();

            // Retrieve all blogs
            $blogsData = $blogsModel
                ->select(['id', 'title', 'description', 'blog_image', 'status'])
                ->findAll();

            $statusCode = 200;
            $response = [
                'data' => $blogsData,
            ];
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 400 ? 400 : 500;
            $response = [
                'error' => $e->getMessage()
            ];
        }

        $response['status'] = $statusCode;
        return $this->respond($response, $statusCode);
    }
    public function update($id = null)
    {
        $blogModel = new BlogsModel();

        // Get the input data
        $data = [
            'title'       => $this->request->getVar('title'),
            'description' => $this->request->getVar('description'),
            'status'      => $this->request->getVar('status'),
            'blog_image'  => $this->request->getVar('blog_image'),
        ];

        // Validate the input
        if (!$this->validate([
            'title'       => 'required|string|max_length[255]',
            'description' => 'required|string',
            'status'      => 'required|in_list[0,1]',
            'blog_image'  => 'permit_empty',
        ])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // Attempt to update the blog
        $updated = $blogModel->update($id, $data);

        // Check if the update was successful
        if ($updated) {
            return $this->respond(['status' => 200, 'message' => 'Blog updated successfully']);
        } else {
            return $this->failNotFound('Blog not found');
        }
    }
}
