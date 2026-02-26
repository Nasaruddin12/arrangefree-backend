<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use App\Models\AdminUserAccessRequestModel;
use App\Models\AdminUserActivityLogModel;
use App\Models\AdminUserImpersonationSessionModel;
use App\Models\CustomerModel;
use CodeIgniter\API\ResponseTrait;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AdminUserAccessController extends BaseController
{
    use ResponseTrait;

    private AdminUserAccessRequestModel $requestModel;
    private AdminUserImpersonationSessionModel $sessionModel;
    private AdminUserActivityLogModel $activityModel;
    private AdminModel $adminModel;
    private CustomerModel $customerModel;

    public function __construct()
    {
        $this->requestModel = new AdminUserAccessRequestModel();
        $this->sessionModel = new AdminUserImpersonationSessionModel();
        $this->activityModel = new AdminUserActivityLogModel();
        $this->adminModel = new AdminModel();
        $this->customerModel = new CustomerModel();
    }

    public function createRequest()
    {
        try {
            $adminId = $this->getAdminIdFromToken();
            if ($adminId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
            }

            $userId = (int) ($this->request->getVar('user_id') ?? 0);
            $accessType = (string) ($this->request->getVar('access_type') ?? 'cart');
            $reason = $this->request->getVar('reason');
            $expiresAt = $this->request->getVar('expires_at');

            if ($userId <= 0) {
                return $this->respond(['status' => 422, 'message' => 'Valid user_id is required.'], 422);
            }

            if (!in_array($accessType, ['cart', 'orders', 'full'], true)) {
                return $this->respond(['status' => 422, 'message' => 'access_type must be cart, orders, or full.'], 422);
            }

            $admin = $this->adminModel->find($adminId);
            if (!$admin) {
                return $this->respond(['status' => 404, 'message' => 'Admin not found.'], 404);
            }

            $customer = $this->customerModel->find($userId);
            if (!$customer) {
                return $this->respond(['status' => 404, 'message' => 'User not found.'], 404);
            }

            if (empty($expiresAt)) {
                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            }

            $this->requestModel->insert([
                'admin_id' => $adminId,
                'user_id' => $userId,
                'access_type' => $accessType,
                'reason' => $reason,
                'status' => 'pending',
                'expires_at' => $expiresAt,
            ]);

            $requestId = (int) $this->requestModel->getInsertID();
            $this->logActivity($adminId, $userId, 'request_created', 'Access request created: #' . $requestId);

            return $this->respond([
                'status' => 201,
                'message' => 'Access request created successfully.',
                'data' => [
                    'request_id' => $requestId,
                    'admin_id' => $adminId,
                    'user_id' => $userId,
                    'access_type' => $accessType,
                    'status' => 'pending',
                    'expires_at' => $expiresAt,
                ],
            ], 201);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function listRequests()
    {
        try {
            $adminId = $this->getAdminIdFromToken();
            if ($adminId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
            }

            $page = max(1, (int) ($this->request->getVar('page') ?? 1));
            $limit = max(1, (int) ($this->request->getVar('limit') ?? 20));
            $offset = ($page - 1) * $limit;

            $status = $this->request->getVar('status');
            $userId = (int) ($this->request->getVar('user_id') ?? 0);

            $builder = $this->requestModel
                ->select('admin_user_access_requests.*, customers.name as user_name, customers.mobile_no as user_mobile')
                ->join('customers', 'customers.id = admin_user_access_requests.user_id', 'left')
                ->where('admin_user_access_requests.admin_id', $adminId)
                ->orderBy('admin_user_access_requests.id', 'DESC');

            if (!empty($status) && in_array($status, ['pending', 'approved', 'rejected', 'expired'], true)) {
                $builder->where('admin_user_access_requests.status', $status);
            }

            if ($userId > 0) {
                $builder->where('admin_user_access_requests.user_id', $userId);
            }

            $total = $builder->countAllResults(false);
            $rows = $builder->findAll($limit, $offset);

            return $this->respond([
                'status' => 200,
                'message' => 'Access requests fetched successfully.',
                'data' => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_records' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateRequestStatus($id)
    {
        try {
            $adminId = $this->getAdminIdFromToken();
            if ($adminId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
            }

            $requestId = (int) $id;
            $status = (string) $this->request->getVar('status');

            if (!in_array($status, ['approved', 'rejected', 'expired'], true)) {
                return $this->respond(['status' => 422, 'message' => 'status must be approved, rejected, or expired.'], 422);
            }

            $requestRow = $this->requestModel
                ->where('id', $requestId)
                ->where('admin_id', $adminId)
                ->first();

            if (!$requestRow) {
                return $this->respond(['status' => 404, 'message' => 'Access request not found.'], 404);
            }

            $updateData = ['status' => $status];
            if ($status === 'approved') {
                $updateData['approved_at'] = date('Y-m-d H:i:s');
                if (empty($requestRow['expires_at'])) {
                    $updateData['expires_at'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                }
            }

            $this->requestModel->update($requestId, $updateData);
            $this->logActivity($adminId, (int) $requestRow['user_id'], 'request_' . $status, 'Access request #' . $requestId . ' marked as ' . $status);

            return $this->respond([
                'status' => 200,
                'message' => 'Access request status updated successfully.',
                'data' => [
                    'request_id' => $requestId,
                    'status' => $status,
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function impersonationLogin()
    {
        try {
            $adminId = $this->getAdminIdFromToken();
            if ($adminId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
            }

            $requestId = (int) ($this->request->getVar('request_id') ?? 0);
            if ($requestId <= 0) {
                return $this->respond(['status' => 422, 'message' => 'Valid request_id is required.'], 422);
            }

            $requestRow = $this->requestModel
                ->where('id', $requestId)
                ->where('admin_id', $adminId)
                ->where('status', 'approved')
                ->first();

            if (!$requestRow) {
                return $this->respond(['status' => 404, 'message' => 'Approved access request not found.'], 404);
            }

            if (!empty($requestRow['expires_at']) && strtotime($requestRow['expires_at']) < time()) {
                $this->requestModel->update($requestId, ['status' => 'expired']);
                return $this->respond(['status' => 410, 'message' => 'Access request has expired.'], 410);
            }

            $customer = $this->customerModel->find((int) $requestRow['user_id']);
            if (!$customer) {
                return $this->respond(['status' => 404, 'message' => 'User not found.'], 404);
            }

            $sessionToken = bin2hex(random_bytes(32));
            $sessionExpiry = !empty($requestRow['expires_at'])
                ? $requestRow['expires_at']
                : date('Y-m-d H:i:s', strtotime('+30 minutes'));

            $this->sessionModel->insert([
                'admin_id' => $adminId,
                'user_id' => (int) $requestRow['user_id'],
                'access_request_id' => $requestId,
                'token' => $sessionToken,
                'expires_at' => $sessionExpiry,
                'is_active' => 1,
            ]);

            $key = getenv('JWT_SECRET');
            $iat = time();
            $exp = strtotime($sessionExpiry);
            if ($exp <= $iat) {
                $exp = $iat + 1800;
            }

            $payload = [
                'iss' => base_url(),
                'aud' => 'AdminImpersonation',
                'sub' => 'Admin login as user',
                'iat' => $iat,
                'exp' => $exp,
                'admin_id' => $adminId,
                'customer_id' => (int) $requestRow['user_id'],
                'request_id' => $requestId,
                'session_token' => $sessionToken,
            ];

            $jwtToken = JWT::encode($payload, $key, 'HS256');
            $this->logActivity($adminId, (int) $requestRow['user_id'], 'impersonation_login', 'Impersonation started for request #' . $requestId);

            unset($customer['password'], $customer['otp']);

            return $this->respond([
                'status' => 200,
                'message' => 'Impersonation login successful.',
                'data' => [
                    'token' => $jwtToken,
                    'session_token' => $sessionToken,
                    'expires_at' => $sessionExpiry,
                    'request_id' => $requestId,
                    'access_type' => $requestRow['access_type'],
                    'user' => $customer,
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function createLoginGrant()
    {
        try {
            $adminId = $this->getAdminIdFromToken();
            if ($adminId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
            }

            $requestId = (int) ($this->request->getVar('request_id') ?? 0);
            if ($requestId <= 0) {
                return $this->respond(['status' => 422, 'message' => 'Valid request_id is required.'], 422);
            }

            $requestRow = $this->requestModel
                ->where('id', $requestId)
                ->where('admin_id', $adminId)
                ->where('status', 'approved')
                ->first();

            if (!$requestRow) {
                return $this->respond(['status' => 404, 'message' => 'Approved access request not found.'], 404);
            }

            if (!empty($requestRow['expires_at']) && strtotime($requestRow['expires_at']) < time()) {
                $this->requestModel->update($requestId, ['status' => 'expired']);
                return $this->respond(['status' => 410, 'message' => 'Access request has expired.'], 410);
            }

            $key = getenv('JWT_SECRET');
            $iat = time();
            $exp = $iat + 300;
            $grantPayload = [
                'iss' => base_url(),
                'aud' => 'AdminImpersonationGrant',
                'sub' => 'Grant for frontend login exchange',
                'iat' => $iat,
                'exp' => $exp,
                'type' => 'impersonation_grant',
                'admin_id' => $adminId,
                'request_id' => $requestId,
            ];

            $grantToken = JWT::encode($grantPayload, $key, 'HS256');
            $frontendUrl = (string) ($this->request->getVar('redirect_url') ?? '');

            $redirectWithToken = '';
            if ($frontendUrl !== '') {
                $separator = str_contains($frontendUrl, '?') ? '&' : '?';
                $redirectWithToken = $frontendUrl . $separator . 'grant_token=' . urlencode($grantToken);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Login grant created successfully.',
                'data' => [
                    'grant_token' => $grantToken,
                    'expires_in_seconds' => 300,
                    'request_id' => $requestId,
                    'redirect_url' => $redirectWithToken,
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function loginWithGrant()
    {
        try {
            $grantToken = (string) ($this->request->getVar('grant_token') ?? '');
            if ($grantToken === '') {
                return $this->respond(['status' => 422, 'message' => 'grant_token is required.'], 422);
            }

            $key = getenv('JWT_SECRET');
            $decoded = JWT::decode($grantToken, new Key($key, 'HS256'));
            $grant = (array) $decoded;

            if (($grant['aud'] ?? '') !== 'AdminImpersonationGrant' || ($grant['type'] ?? '') !== 'impersonation_grant') {
                return $this->respond(['status' => 401, 'message' => 'Invalid grant token.'], 401);
            }

            $adminId = (int) ($grant['admin_id'] ?? 0);
            $requestId = (int) ($grant['request_id'] ?? 0);
            if ($adminId <= 0 || $requestId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Invalid grant payload.'], 401);
            }

            $requestRow = $this->requestModel
                ->where('id', $requestId)
                ->where('admin_id', $adminId)
                ->where('status', 'approved')
                ->first();

            if (!$requestRow) {
                return $this->respond(['status' => 404, 'message' => 'Approved access request not found.'], 404);
            }

            if (!empty($requestRow['expires_at']) && strtotime($requestRow['expires_at']) < time()) {
                $this->requestModel->update($requestId, ['status' => 'expired']);
                return $this->respond(['status' => 410, 'message' => 'Access request has expired.'], 410);
            }

            $customer = $this->customerModel->find((int) $requestRow['user_id']);
            if (!$customer) {
                return $this->respond(['status' => 404, 'message' => 'User not found.'], 404);
            }

            $sessionToken = bin2hex(random_bytes(32));
            $sessionExpiry = !empty($requestRow['expires_at'])
                ? $requestRow['expires_at']
                : date('Y-m-d H:i:s', strtotime('+30 minutes'));

            $this->sessionModel->insert([
                'admin_id' => $adminId,
                'user_id' => (int) $requestRow['user_id'],
                'access_request_id' => $requestId,
                'token' => $sessionToken,
                'expires_at' => $sessionExpiry,
                'is_active' => 1,
            ]);

            $iat = time();
            $exp = strtotime($sessionExpiry);
            if ($exp <= $iat) {
                $exp = $iat + 1800;
            }

            $payload = [
                'iss' => base_url(),
                'aud' => 'AdminImpersonation',
                'sub' => 'Admin login as user',
                'iat' => $iat,
                'exp' => $exp,
                'admin_id' => $adminId,
                'customer_id' => (int) $requestRow['user_id'],
                'request_id' => $requestId,
                'session_token' => $sessionToken,
            ];

            $jwtToken = JWT::encode($payload, $key, 'HS256');
            $this->logActivity($adminId, (int) $requestRow['user_id'], 'impersonation_login', 'Impersonation started for request #' . $requestId);

            unset($customer['password'], $customer['otp']);

            return $this->respond([
                'status' => 200,
                'message' => 'Frontend login successful.',
                'data' => [
                    'token' => $jwtToken,
                    'session_token' => $sessionToken,
                    'expires_at' => $sessionExpiry,
                    'request_id' => $requestId,
                    'access_type' => $requestRow['access_type'],
                    'user' => $customer,
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 401, 'message' => 'Invalid or expired grant token.'], 401);
        }
    }

    public function impersonationLogout()
    {
        try {
            $adminId = $this->getAdminIdFromToken();
            if ($adminId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
            }

            $sessionToken = (string) ($this->request->getVar('session_token') ?? '');
            if ($sessionToken === '') {
                return $this->respond(['status' => 422, 'message' => 'session_token is required.'], 422);
            }

            $session = $this->sessionModel
                ->where('token', $sessionToken)
                ->where('admin_id', $adminId)
                ->first();

            if (!$session) {
                return $this->respond(['status' => 404, 'message' => 'Session not found.'], 404);
            }

            $this->sessionModel->update((int) $session['id'], ['is_active' => 0]);
            $this->logActivity($adminId, (int) $session['user_id'], 'impersonation_logout', 'Impersonation ended for session #' . $session['id']);

            return $this->respond([
                'status' => 200,
                'message' => 'Impersonation session closed successfully.',
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function validateSession()
    {
        try {
            $adminId = $this->getAdminIdFromToken();
            if ($adminId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
            }

            $sessionToken = (string) ($this->request->getVar('session_token') ?? '');
            if ($sessionToken === '') {
                return $this->respond(['status' => 422, 'message' => 'session_token is required.'], 422);
            }

            $session = $this->sessionModel->getValidSession($sessionToken);
            if (!$session || (int) $session['admin_id'] !== $adminId) {
                return $this->respond([
                    'status' => 200,
                    'message' => 'Session is invalid or expired.',
                    'data' => ['is_valid' => false],
                ], 200);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Session is valid.',
                'data' => [
                    'is_valid' => true,
                    'session' => $session,
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function logs()
    {
        try {
            $adminId = $this->getAdminIdFromToken();
            if ($adminId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
            }

            $page = max(1, (int) ($this->request->getVar('page') ?? 1));
            $limit = max(1, (int) ($this->request->getVar('limit') ?? 20));
            $offset = ($page - 1) * $limit;

            $userId = (int) ($this->request->getVar('user_id') ?? 0);
            $action = (string) ($this->request->getVar('action') ?? '');

            $builder = $this->activityModel
                ->where('admin_id', $adminId)
                ->orderBy('id', 'DESC');

            if ($userId > 0) {
                $builder->where('user_id', $userId);
            }

            if ($action !== '') {
                $builder->where('action', $action);
            }

            $total = $builder->countAllResults(false);
            $rows = $builder->findAll($limit, $offset);

            return $this->respond([
                'status' => 200,
                'message' => 'Activity logs fetched successfully.',
                'data' => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_records' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function requestLogs($id)
    {
        try {
            $adminId = $this->getAdminIdFromToken();
            if ($adminId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized admin token.'], 401);
            }

            $requestId = (int) $id;
            if ($requestId <= 0) {
                return $this->respond(['status' => 422, 'message' => 'Valid request id is required.'], 422);
            }

            $requestRow = $this->requestModel
                ->where('id', $requestId)
                // ->where('admin_id', $adminId)
                ->first();

            if (!$requestRow) {
                return $this->respond(['status' => 404, 'message' => 'Access request not found.'], 404);
            }

            $page = max(1, (int) ($this->request->getVar('page') ?? 1));
            $limit = max(1, (int) ($this->request->getVar('limit') ?? 20));
            $offset = ($page - 1) * $limit;
            $action = (string) ($this->request->getVar('action') ?? '');
            $sessionIds = $this->sessionModel
                ->select('id')
                ->where('access_request_id', $requestId)
                // ->where('admin_id', $adminId)
                ->findColumn('id') ?? [];

            $builder = $this->activityModel
                // ->where('admin_id', $adminId)
                ->where('user_id', (int) $requestRow['user_id'])
                ->groupStart()
                ->like('description', '#' . $requestId);

            foreach ($sessionIds as $sessionId) {
                $builder->orLike('description', 'session #' . (int) $sessionId);
            }

            $builder->groupEnd()
                ->orderBy('id', 'DESC');

            if ($action !== '') {
                $builder->where('action', $action);
            }

            $total = $builder->countAllResults(false);
            $rows = $builder->findAll($limit, $offset);

            return $this->respond([
                'status' => 200,
                'message' => 'Request logs fetched successfully.',
                'request_id' => $requestId,
                'data' => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_records' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function userAccessRequests()
    {
        try {
            $customerId = $this->getCustomerIdFromToken();
            if ($customerId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized user token.'], 401);
            }

            $page = max(1, (int) ($this->request->getVar('page') ?? 1));
            $limit = max(1, (int) ($this->request->getVar('limit') ?? 20));
            $offset = ($page - 1) * $limit;

            $status = (string) ($this->request->getVar('status') ?? '');
            $allowedStatuses = ['pending', 'approved'];

            $builder = $this->requestModel
                ->select('admin_user_access_requests.*, admins.name as admin_name, admins.mobile_no as admin_mobile')
                ->join('admins', 'admins.id = admin_user_access_requests.admin_id', 'left')
                ->where('admin_user_access_requests.user_id', $customerId)
                ->orderBy('admin_user_access_requests.id', 'DESC');

            if ($status !== '' && in_array($status, $allowedStatuses, true)) {
                $builder->where('admin_user_access_requests.status', $status);
            } else {
                $builder->whereIn('admin_user_access_requests.status', $allowedStatuses);
            }

            $total = $builder->countAllResults(false);
            $rows = $builder->findAll($limit, $offset);

            return $this->respond([
                'status' => 200,
                'message' => 'User access requests fetched successfully.',
                'data' => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_records' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function userRespondRequest($id, $decision = null)
    {
        try {
            $customerId = $this->getCustomerIdFromToken();
            if ($customerId <= 0) {
                return $this->respond(['status' => 401, 'message' => 'Unauthorized user token.'], 401);
            }

            $requestId = (int) $id;
            if ($requestId <= 0) {
                return $this->respond(['status' => 422, 'message' => 'Valid request id is required.'], 422);
            }

            $decision = strtolower((string) ($decision ?? $this->request->getVar('decision') ?? $this->request->getVar('status')));
            if (!in_array($decision, ['approve', 'approved', 'reject', 'rejected', 'revoke', 'revoked'], true)) {
                return $this->respond([
                    'status' => 422,
                    'message' => 'decision/status must be approve, reject, or revoke.',
                ], 422);
            }

            $isApprove = in_array($decision, ['approve', 'approved'], true);
            $isReject = in_array($decision, ['reject', 'rejected'], true);
            $isRevoke = in_array($decision, ['revoke', 'revoked'], true);
            $targetStatus = $isApprove ? 'approved' : ($isReject ? 'rejected' : 'expired');

            $requestRow = $this->requestModel
                ->where('id', $requestId)
                ->where('user_id', $customerId)
                ->first();

            if (!$requestRow) {
                return $this->respond(['status' => 404, 'message' => 'Access request not found.'], 404);
            }

            if ($isRevoke) {
                if ($requestRow['status'] !== 'approved') {
                    return $this->respond([
                        'status' => 409,
                        'message' => 'Only approved requests can be revoked.',
                        'data' => ['current_status' => $requestRow['status']],
                    ], 409);
                }
            } elseif ($requestRow['status'] !== 'pending') {
                return $this->respond([
                    'status' => 409,
                    'message' => 'Only pending requests can be approved or rejected.',
                    'data' => ['current_status' => $requestRow['status']],
                ], 409);
            }

            if (!empty($requestRow['expires_at']) && strtotime($requestRow['expires_at']) < time()) {
                $this->requestModel->update($requestId, ['status' => 'expired']);
                return $this->respond(['status' => 410, 'message' => 'Access request has expired.'], 410);
            }

            $updateData = ['status' => $targetStatus];
            $expiresAt = $requestRow['expires_at'];

            if ($targetStatus === 'approved') {
                if (empty($expiresAt)) {
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                }
                $updateData['approved_at'] = date('Y-m-d H:i:s');
                $updateData['expires_at'] = $expiresAt;
            } elseif ($isRevoke) {
                $expiresAt = date('Y-m-d H:i:s');
                $updateData['expires_at'] = $expiresAt;
            }

            $this->requestModel->update($requestId, $updateData);

            if ($isRevoke) {
                $this->sessionModel
                    ->where('access_request_id', $requestId)
                    ->where('admin_id', (int) $requestRow['admin_id'])
                    ->set(['is_active' => 0])
                    ->update();
            }

            $action = $isApprove
                ? 'request_approved_by_user'
                : ($isReject ? 'request_rejected_by_user' : 'request_revoked_by_user');
            $this->logActivity((int) $requestRow['admin_id'], $customerId, $action, 'User ' . $targetStatus . ' access request #' . $requestId);

            return $this->respond([
                'status' => 200,
                'message' => 'Request ' . $targetStatus . ' successfully.',
                'data' => [
                    'request_id' => $requestId,
                    'status' => $targetStatus,
                    'expires_at' => $expiresAt,
                ],
            ], 200);
        } catch (Exception $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    private function getAdminIdFromToken(): int
    {
        $authUser = session('auth_user') ?? [];
        if (isset($authUser['id'])) {
            return (int) $authUser['id'];
        }

        if (isset($authUser['admin_id'])) {
            return (int) $authUser['admin_id'];
        }

        return 0;
    }

    private function getCustomerIdFromToken(): int
    {
        $authUser = session('auth_user') ?? [];
        if (isset($authUser['customer_id'])) {
            return (int) $authUser['customer_id'];
        }

        return 0;
    }

    private function logActivity(int $adminId, int $userId, string $action, string $description): void
    {
        $this->activityModel->insert([
            'admin_id' => $adminId,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => (string) $this->request->getIPAddress(),
        ]);
    }
}
