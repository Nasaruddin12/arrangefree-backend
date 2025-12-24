<?php

namespace App\Controllers;

use Config\Services;

class TestController extends BaseController
{
    public function testGcm()
    {
        $encrypter = Services::encrypter();

        $plain = json_encode([
            'status' => true,
            'msg' => 'GCM finally works'
        ]);

        $encrypted = base64_encode($encrypter->encrypt($plain));
        $decrypted = $encrypter->decrypt(base64_decode($encrypted));

        return $this->response->setJSON([
            'encrypted_ok' => true,
            'decrypted' => json_decode($decrypted, true),
        ]);
    }
}
