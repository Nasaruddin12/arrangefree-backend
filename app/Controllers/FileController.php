<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class FileController extends Controller
{
    public function serveFile($filename)
    {
        $filePath = 'public/uploads/assets/' . $filename;

        if (!file_exists($filePath)) {
            return $this->response->setStatusCode(404)->setBody('File not found');
        }

        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*') // You can restrict to a domain
            ->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->download($filePath, null); // Triggers file download
    }
}
