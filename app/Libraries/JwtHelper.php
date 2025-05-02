<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private $key;
    private $algo;

    public function __construct()
    {
        $this->key  = getenv('JWT_SECRET') ?? 'your_super_secret_key';
        $this->algo = 'HS256';
    }

    public function generateToken(array $payload, $expireInMinutes = 300000)
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + ($expireInMinutes * 60);
        return JWT::encode($payload, $this->key, $this->algo);
    }

    public function decodeToken(string $token)
    {
        return JWT::decode($token, new Key($this->key, $this->algo));
    }
}
