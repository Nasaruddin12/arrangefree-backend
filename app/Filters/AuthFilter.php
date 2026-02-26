<?php

namespace App\Filters;

use App\Models\AdminUserAccessRequestModel;
use App\Models\AdminUserImpersonationSessionModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $key = getenv('JWT_SECRET');
        $header = $request->getHeaderLine("Authorization");
        $token = null;
        
        // extract the token from the header
        if (!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                $token = $matches[1];
            }
        }
        
        // check if token is null or empty
        if (is_null($token) || empty($token)) {
            $response = service('response');
            return $response
                ->setStatusCode(401)
                ->setJSON([
                'success' => false,
                'message' => 'No token provided'
            ]);
        }
        
        try {
            // $decoded = JWT::decode($token, $key, array("HS256"));
            // die(var_dump($token));
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $decodedData = (array) $decoded;

            // For impersonation JWT, enforce DB-backed validity by request/session state.
            if (($decodedData['aud'] ?? '') === 'AdminImpersonation') {
                $adminId = (int) ($decodedData['admin_id'] ?? 0);
                $customerId = (int) ($decodedData['customer_id'] ?? 0);
                $requestId = (int) ($decodedData['request_id'] ?? 0);

                if ($adminId <= 0 || $customerId <= 0 || $requestId <= 0) {
                    $response = service('response');
                    return $response
                        ->setStatusCode(401)
                        ->setJSON([
                            'success' => false,
                            'message' => 'Access denied',
                            'error' => 'Invalid impersonation token payload'
                        ]);
                }

                $requestModel = new AdminUserAccessRequestModel();
                $sessionModel = new AdminUserImpersonationSessionModel();

                $requestRow = $requestModel
                    ->where('id', $requestId)
                    ->where('admin_id', $adminId)
                    ->where('user_id', $customerId)
                    ->where('status', 'approved')
                    ->first();

                $now = date('Y-m-d H:i:s');
                if (
                    !$requestRow
                    || (!empty($requestRow['expires_at']) && strtotime((string) $requestRow['expires_at']) < time())
                ) {
                    $response = service('response');
                    return $response
                        ->setStatusCode(401)
                        ->setJSON([
                            'success' => false,
                            'message' => 'Access denied',
                            'error' => 'Impersonation access is expired or revoked'
                        ]);
                }

                $activeSession = $sessionModel
                    ->where('admin_id', $adminId)
                    ->where('user_id', $customerId)
                    ->where('access_request_id', $requestId)
                    ->where('is_active', 1)
                    ->where('expires_at >=', $now)
                    ->first();

                if (!$activeSession) {
                    $response = service('response');
                    return $response
                        ->setStatusCode(401)
                        ->setJSON([
                            'success' => false,
                            'message' => 'Access denied',
                            'error' => 'Impersonation session is inactive'
                        ]);
                }
            }

            // Store decoded token in session for access in controllers
            $session = session();
            $session->set('auth_user', $decodedData);
        } catch (Exception $ex) {
            $response = service('response');
            return $response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'Access denied',
                    'error' => 'Invalid or expired token'
                ]);
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
