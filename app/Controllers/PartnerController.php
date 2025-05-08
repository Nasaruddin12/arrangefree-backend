<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\SMSGateway;
use App\Models\PartnerBankDetailModel;
use App\Models\PartnerModel;
use App\Models\PartnerOtpModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class PartnerController extends BaseController
{
    use ResponseTrait;

    protected $partnerModel;
    protected $partnerOtpModel;
    protected $partnerBankDetailModel;


    public function __construct()
    {
        $this->partnerModel = new PartnerModel();
        $this->partnerOtpModel = new PartnerOtpModel();
        $this->partnerBankDetailModel = new PartnerBankDetailModel();
    }

    /**
     * Fetch all partners
     */
    public function index()
    {
        try {
            $partners = $this->partnerModel->findAll();

            return $this->respond([
                'status' => 200,
                'message' => 'Partners retrieved successfully',
                'data' => $partners
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to fetch partners: ' . $e->getMessage());
        }
    }

    /**
     * Show a single partner by ID
     */
    public function profile($id = null)
    {
        try {
            if (!$id) {
                return $this->respond(['status' => 400, 'message' => 'Partner ID is required'], 400);
            }

            $partner = (new \App\Models\PartnerModel())->find($id);

            if (!$partner) {
                return $this->respond(['status' => 404, 'message' => 'Partner not found'], 404);
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Partner profile retrieved successfully',
                'data' => $partner
            ], 200);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function onboardingStatus()
    {
        try {
            $partnerId = $this->request->getVar('partner_id');

            if (!$partnerId) {
                return $this->respond(['status' => 400, 'message' => 'Partner ID is required'], 400);
            }

            $partner = (new \App\Models\PartnerModel())->find($partnerId);

            if (!$partner) {
                return $this->respond(['status' => 404, 'message' => 'Partner not found'], 404);
            }

            $status = [
                'mobile_verified'   => (bool) $partner['mobile_verified'],
                'personal_details'  => !empty($partner['dob']) && !empty($partner['work']),
                'documents_verified' => (bool) $partner['documents_verified'],
                'bank_verified'     => (bool) $partner['bank_verified'],
            ];

            return $this->respond([
                'status' => 200,
                'partner_id' => $partnerId,
                'onboarding_status' => $status
            ], 200);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function sendOtp()
    {
        try {
            $mobile = $this->request->getVar('mobile');
            $type   = $this->request->getVar('type'); // 'login' or 'register'

            if (!preg_match('/^[0-9]{10}$/', $mobile)) {
                throw new \Exception('Invalid mobile number', 422);
            }

            if (!in_array($type, ['login', 'register'])) {
                throw new \Exception('Invalid request type', 422);
            }

            $partner = $this->partnerModel->where('mobile', $mobile)->first();

            if ($type === 'register' && $partner) {
                throw new \Exception('Mobile already registered. Please login.', 409);
            }

            if ($type === 'login' && !$partner) {
                throw new \Exception('Mobile number not found. Please register first.', 404);
            }

            $otpModel = new \App\Models\PartnerOtpModel();

            // Block check
            $lastOtp = $otpModel->where('mobile', $mobile)->orderBy('id', 'desc')->first();
            if ($lastOtp && $lastOtp['otp_blocked_until'] && strtotime($lastOtp['otp_blocked_until']) > time()) {
                throw new \Exception('Too many OTP requests. You are temporarily blocked.', 429);
            }

            // Throttle: max 3 in 2 minutes
            $recentOtpsCount = $otpModel
                ->where('mobile', $mobile)
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-2 minutes')))
                ->countAllResults();

            if ($recentOtpsCount >= 3) {
                $otpModel->insert([
                    'mobile'            => $mobile,
                    'otp'               => $lastOtp['otp'] ?? null,
                    'expires_at'        => $lastOtp['expires_at'] ?? null,
                    'otp_blocked_until' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
                ]);
                throw new \Exception('Too many OTPs. You are blocked for 10 minutes.', 429);
            }

            // ✅ Generate & Save OTP
            $otp = rand(1000, 9999);
            $otpModel->insert([
                'mobile'     => $mobile,
                'otp'        => $otp,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+5 minutes'))
            ]);

            // Send SMS
            $smsGateway = new \App\Libraries\SMSGateway();
            $response = $smsGateway->sendOTP($mobile, $otp);
            if (!isset($response->statusCode) || $response->statusCode != 200) {
                throw new \Exception('Unable to send OTP. SMS failed.', 500);
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'OTP sent successfully',
                'otp'     => $otp // ⚠️ dev only
            ], 200);
        } catch (\Exception $e) {
            return $this->respond([
                'status'  => $e->getCode() ?: 500,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function verifyOtp()
    {
        $mobile = $this->request->getVar('mobile');
        $otp    = $this->request->getVar('otp');

        if (!$mobile || !$otp) {
            return $this->respond(['status' => 422, 'message' => 'Mobile and OTP are required'], 422);
        }

        $otpModel = new \App\Models\PartnerOtpModel();

        // Find matching valid OTP
        $record = $otpModel
            ->where('mobile', $mobile)
            ->where('otp', $otp)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'desc')
            ->first();

        if (!$record) {
            return $this->respond([
                'status'  => 401,
                'message' => 'Invalid or expired OTP'
            ], 401);
        }
        return $this->respond([
            'status'  => 200,
            'message' => 'OTP verified successfully'
        ], 200);
    }

    public function login()
    {
        try {
            $mobile = $this->request->getVar('mobile');
            $otp    = $this->request->getVar('otp');

            if (!preg_match('/^[0-9]{10}$/', $mobile) || empty($otp)) {
                throw new \Exception('Mobile and OTP are required', 422);
            }

            $partner = $this->partnerModel->where('mobile', $mobile)->first();
            if (!$partner) {
                throw new \Exception('Mobile number not registered', 404);
            }

            $otpModel = new \App\Models\PartnerOtpModel();
            $otpRow = $otpModel->where('mobile', $mobile)
                ->where('otp', $otp)
                ->where('expires_at >=', date('Y-m-d H:i:s'))
                ->orderBy('id', 'desc')
                ->first();

            if (!$otpRow) {
                throw new \Exception('Invalid or expired OTP', 401);
            }

            // Optional: mark mobile_verified
            if (!$partner['mobile_verified']) {
                $this->partnerModel->update($partner['id'], ['mobile_verified' => 1]);
            }

            // ✅ Check onboarding flags
            $onboardingStatus = [
                'mobile_verified'     => $partner['mobile_verified'] ? 'verified' : 'pending',
                'documents_verified'  => $partner['documents_verified'] ?? 'pending', // 'pending', 'verified', 'rejected'
                'bank_verified'       => $partner['bank_verified'] ?? 'pending',      // 'pending', 'verified', 'rejected'
                'is_onboarding_complete' => (
                    $partner['mobile_verified'] &&
                    $partner['documents_verified'] === 'verified' &&
                    $partner['bank_verified'] === 'verified'
                )
            ];

            // ✅ Generate JWT Token
            $jwt = new \App\Libraries\JwtHelper();
            $token = $jwt->generateToken([
                'id'     => $partner['id'],
                'mobile' => $partner['mobile'],
                'name'   => $partner['name']
            ]);

            return $this->respond([
                'status'  => 200,
                'message' => 'Login successful',
                'token'   => $token,
                'data'    => $partner,
                'onboarding_status' => $onboardingStatus
            ], 200);
        } catch (\Exception $e) {
            return $this->respond([
                'status'  => $e->getCode() ?: 500,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }
    public function registerOrUpdate()
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $request    = $this->request;
            $partnerId  = $request->getVar('partner_id');
            $isUpdate   = !empty($partnerId);

            $partnerModel = new \App\Models\PartnerModel();
            $docModel     = new \App\Models\PartnerDocumentModel();
            $bankModel    = new \App\Models\PartnerBankDetailModel();

            // Step 1: Validate and Save Partner Info
            $partnerData = [
                'id'               => $partnerId,
                'name'             => $request->getVar('name'),
                'mobile'           => $request->getVar('mobile'),
                'mobile_verified'  => $request->getVar('mobile_verified') ?? true,
                'dob'              => $request->getVar('dob'),
                'work'             => $request->getVar('work'),
                'labour_count'     => $request->getVar('labour_count'),
                'area'             => $request->getVar('area'),
                'service_areas'    => $request->getVar('service_areas'),
                'aadhaar_no'       => $request->getVar('aadhaar_no'),
                'pan_no'           => $request->getVar('pan_no'),
            ];

            if ($isUpdate) {
                $partnerModel->setValidationRules([
                    'mobile'     => 'required|regex_match[/^[0-9]{10}$/]|is_unique[partners.mobile,id,' . $partnerId . ']',
                    'aadhaar_no' => 'required|regex_match[/^[0-9]{12}$/]|is_unique[partners.aadhaar_no,id,' . $partnerId . ']',
                    'pan_no'     => 'required|regex_match[/^[A-Z]{5}[0-9]{4}[A-Z]$/]|is_unique[partners.pan_no,id,' . $partnerId . ']',
                ]);
            }

            if (!$partnerModel->validate($partnerData)) {
                throw new \Exception(json_encode($partnerModel->errors()), 422);
            }

            if ($isUpdate) {
                $partnerModel->update($partnerId, $partnerData);
            } else {
                $partnerModel->insert($partnerData);
                $partnerId = $partnerModel->getInsertID();
            }

            // Step 2: Handle Partner Documents
            $docTypes = [
                'aadhar_front'  => 'aadhar_front',
                'aadhar_back'   => 'aadhar_back',
                'pan_card'      => 'pan_card',
                'address_proof' => 'address_proof',
                'photo'         => 'photo',
            ];

            foreach ($docTypes as $field => $type) {
                $file = $request->getFile($field);
                if ($file && $file->isValid()) {
                    $fileName = $file->getRandomName();

                    $uploadPath = 'public/uploads/partner_docs/';
                    $file->move($uploadPath, $fileName);
                    $docPath = $uploadPath . $fileName;

                    $docData = [
                        'partner_id' => $partnerId,
                        'type'       => $type,
                        'file_path'  => $docPath,
                        'status'     => 'pending'
                    ];

                    $existing = $docModel->where('partner_id', $partnerId)->where('type', $type)->first();
                    if ($existing) {
                        $docModel->update($existing['id'], $docData);
                    } else {
                        $docModel->insert($docData);
                    }
                }
            }

            // Step 3: Validate and Save Bank Details
            $bankData = [
                'partner_id'          => $partnerId,
                'account_holder_name' => $request->getVar('account_holder_name'),
                'bank_name'           => $request->getVar('bank_name'),
                'bank_branch'         => $request->getVar('bank_branch'),
                'account_number'      => $request->getVar('account_number'),
                'ifsc_code'           => $request->getVar('ifsc_code'),
                'status'              => 'pending',
            ];

            $bankFile = $request->getFile('bank_document');
            $existingBank = $bankModel->where('partner_id', $partnerId)->first();

            if (!$existingBank && (!$bankFile || !$bankFile->isValid())) {
                throw new \Exception(json_encode(['bank_document' => 'Bank document is required for new registration.']), 422);
            }

            if ($bankFile && $bankFile->isValid()) {
                $bankFileName = $bankFile->getRandomName();
                $uploadPath = 'public/uploads/partner_docs/';
                
                $file->move($uploadPath, $bankFileName);
                $docPath = $uploadPath . $bankFileName;
                $bankData['bank_document'] = $docPath;
            }

            if (!$bankModel->validate($bankData)) {
                throw new \Exception(json_encode($bankModel->errors()), 422);
            }

            if ($existingBank) {
                $bankModel->update($existingBank['id'], $bankData);
            } else {
                $bankModel->insert($bankData);
            }

            $db->transComplete();
            if (!$db->transStatus()) {
                throw new \Exception("Transaction failed", 500);
            }

            return $this->respond([
                'status'     => 200,
                'message'    => $isUpdate ? 'Partner updated successfully.' : 'Partner registered successfully.',
                'partner_id' => $partnerId
            ], 200);
        } catch (\Exception $e) {
            $db->transRollback();
            $statusCode = $e->getCode() ?: 500;
            $message = $e->getMessage();

            // Check if message is JSON (validation error object)
            $decoded = json_decode($message, true);
            $response = [
                'status'  => $statusCode,
                'message' => $statusCode === 422 ? 'Validation failed' : $message,
            ];

            if (is_array($decoded)) {
                $response['errors'] = $decoded;
            }

            return $this->respond($response, $statusCode);
        }
    }

    public function onboardingData($id = null)
    {
        try {
            // $partnerId = $this->request->getVar('partner_id');

            if (!$id) {
                return $this->respond([
                    'status' => 400,
                    'message' => 'Partner ID is required'
                ], 400);
            }

            $partnerModel = new \App\Models\PartnerModel();
            $docModel     = new \App\Models\PartnerDocumentModel();
            $bankModel    = new \App\Models\PartnerBankDetailModel();

            $partner = $partnerModel->find($id);
            if (!$partner) {
                return $this->respond([
                    'status' => 404,
                    'message' => 'Partner not found'
                ], 404);
            }

            $documents = $docModel
                ->where('partner_id', $id)
                ->findAll();

            $bank = $bankModel
                ->where('partner_id', $id)
                ->first();

            $onboardingStatus = [
                'mobile_verified'     => $partner['mobile_verified'] ? 'verified' : 'pending',
                'documents_verified'  => $partner['documents_verified'] ?? 'pending', // 'pending', 'verified', 'rejected'
                'bank_verified'       => $partner['bank_verified'] ?? 'pending',      // 'pending', 'verified', 'rejected'
                'is_onboarding_complete' => (
                    $partner['mobile_verified'] &&
                    $partner['documents_verified'] === 'verified' &&
                    $partner['bank_verified'] === 'verified'
                )
            ];
            return $this->respond([
                'status' => 200,
                'message' => 'Onboarding data retrieved successfully',
                'data' => [
                    'partner'       => $partner,
                    'documents'     => $documents,
                    'bank_details'  => $bank ?? (object) [],
                    'onboarding_status' => $onboardingStatus
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 500,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
