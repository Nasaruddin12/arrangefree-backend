<?php

use Config\Services;

function encrypt_response(array $data): array
{
    $encrypter = Services::encrypter();

    $plaintext = json_encode($data, JSON_UNESCAPED_UNICODE);

    $cipherBinary = $encrypter->encrypt($plaintext);
    $cipherBase64 = base64_encode($cipherBinary);

    $hmacKey = base64_decode(env('encryption.hmac'));
    $hmac    = hash_hmac('sha256', $cipherBase64, $hmacKey);

    return [
        'encrypted' => true,
        'algo'      => 'AES-256-CBC',
        'v'         => 1,
        'payload'   => [
            'cipher' => $cipherBase64,
            'hmac'   => $hmac,
        ],
    ];
}
