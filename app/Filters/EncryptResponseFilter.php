<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class EncryptResponseFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null) {}

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! str_contains($response->getHeaderLine('Content-Type'), 'application/json')) {
            return $response;
        }

        $body = $response->getBody();
        if (! $body) {
            return $response;
        }

        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            return $response;
        }

        helper('encryption_helper');

        return $response
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode(encrypt_response($decoded)));
    }
}
