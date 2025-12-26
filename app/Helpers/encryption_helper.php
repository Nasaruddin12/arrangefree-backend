<?php

use Config\Services;

function encrypt_response(array $data): array
{
    $plaintext = json_encode($data, JSON_UNESCAPED_UNICODE);

    $key = env('encryption.key');
    $hmacKey = env('encryption.hmac');

    if (strlen($key) !== 32 || strlen($hmacKey) !== 32) {
        throw new RuntimeException('Keys must be exactly 32 characters');
    }

    $iv = random_bytes(16);

    $cipherRaw = openssl_encrypt(
        $plaintext,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    if ($cipherRaw === false) {
        throw new RuntimeException('OpenSSL encryption failed');
    }

    // 🔐 Encrypt-then-MAC
    $macRaw = hash_hmac(
        'sha256',
        $iv . $cipherRaw,   // RAW BYTES
        $hmacKey,
        true                // RAW OUTPUT
    );

    // Encode for JSON transport
    $macB64 = base64_encode($macRaw);
    return [
        'encrypted' => true,
        // 'algo'      => 'AES-256-CBC',
        'v'         => 1,
        'payload'   => [
            'cipher' => base64_encode($cipherRaw),
            'iv'     => base64_encode($iv),
            'hmac'   => $macB64,
        ],
    ];
}
