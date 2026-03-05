<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;
use RuntimeException;
use Throwable;

class ImageProcessingService
{
    public function uploadAndConvertToWebp(
        UploadedFile $imageFile,
        string $uploadPath,
        ?string $baseName = null,
        int $maxWidth = 1200,
        int $maxHeight = 1200,
        int $quality = 90
    ): string {
        if (!$imageFile->isValid()) {
            throw new RuntimeException('Invalid uploaded file.');
        }

        if ($imageFile->hasMoved()) {
            throw new RuntimeException('Uploaded file has already been moved.');
        }

        $normalizedUploadPath = rtrim($uploadPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($normalizedUploadPath) && !mkdir($normalizedUploadPath, 0755, true) && !is_dir($normalizedUploadPath)) {
            throw new RuntimeException('Failed to create upload directory: ' . $normalizedUploadPath);
        }

        $tempName = $imageFile->getRandomName();
        $finalBaseName = $baseName ?: pathinfo($imageFile->getRandomName(), PATHINFO_FILENAME);
        $webpName = $finalBaseName . '.webp';

        $originalFullPath = $normalizedUploadPath . $tempName;
        $webpFullPath = $normalizedUploadPath . $webpName;

        try {
            $imageFile->move($normalizedUploadPath, $tempName);

            Services::image()
                ->withFile($originalFullPath)
                ->resize($maxWidth, $maxHeight, true, 'width')
                ->convert(IMAGETYPE_WEBP)
                ->save($webpFullPath, $quality);
        } catch (Throwable $e) {
            if (file_exists($originalFullPath)) {
                @unlink($originalFullPath);
            }

            throw new RuntimeException('Failed to process image: ' . $e->getMessage(), 0, $e);
        }

        if (file_exists($originalFullPath)) {
            @unlink($originalFullPath);
        }

        return $webpName;
    }
}
