<?php

namespace App\Controllers;

use DirectoryIterator;
use Exception;

class Home extends BaseController
{
    public function index()
    {
        return view('welcome_message');
    }

    public function generateThumbnailImages()
    {
        try {
            $image = \Config\Services::image();
            $path = 'public/uploads/products/900x500';
            $dir = new DirectoryIterator($path);
            foreach ($dir as $productImage) {
                if (!$image->isDot()) {
                    $image->withFile($productImage)
                        ->resize(1080, 1620, true)
                        ->convert(IMAGETYPE_JPEG)
                        ->save('upload/products/900x500/.' . '-' . bin2hex(random_bytes(10)) . '.jpeg', 90);
                }
            }
            die;
            // $images = glob($path . '/*');
            /* foreach ($images as $image) {
                if (is_file($image)) {
                    echo $image->getFileName();
                }
            } */
        } catch (Exception $e) {
        }
    }
}
