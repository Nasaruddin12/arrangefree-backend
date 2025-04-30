<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\PartnerBankDetailModel;
use App\Models\PartnerModel;
use App\Models\PartnerOtpModel;
use CodeIgniter\API\ResponseTrait;

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
     * Register a new partner
     */
    public function register()
    {
        try {
            $data = $this->request->getPost();

            // Validate input
            if (!$this->partnerModel->validate($data)) {
                return $this->respond([
                    'status'  => 422,
                    'message' => 'Validation failed',
                    'errors'  => $this->partnerModel->errors(),
                ], 422);
            }

            // Insert partner
            $this->partnerModel->insert($data);

            return $this->respond([
                'status'  => 201,
                'message' => 'Partner registered successfully',
                'data'    => $data
            ], 201);
        } catch (\Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
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
    public function show($id = null)
    {
        try {
            $partner = $this->partnerModel->find($id);

            if (!$partner) {
                return $this->failNotFound('Partner not found');
            }

            return $this->respond([
                'status' => 200,
                'message' => 'Partner found',
                'data' => $partner
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    public function sendOtp()
    {
        $mobile = $this->request->getPost('mobile');

        if (!preg_match('/^[0-9]{10}$/', $mobile)) {
            return $this->respond(['status' => 422, 'message' => 'Invalid mobile number'], 422);
        }

        // ✅ Check if mobile already registered in partners table
        $partner = $this->partnerModel->where('mobile', $mobile)->first();

        if ($partner) {
            return $this->respond([
                'status'  => 409,
                'message' => 'Mobile number already registered. Please login or use a different number.'
            ], 409);
        }

        $otpModel = new \App\Models\PartnerOtpModel();

        // 🛑 Check if user is blocked
        $lastOtp = $otpModel
            ->where('mobile', $mobile)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOtp && $lastOtp['otp_blocked_until'] && strtotime($lastOtp['otp_blocked_until']) > time()) {
            return $this->respond([
                'status'  => 429,
                'message' => 'Too many OTP requests. You are temporarily blocked. Try again later.'
            ], 429);
        }

        // 🕑 Check for 3 OTPs in last 2 minutes
        $recentOtpsCount = $otpModel
            ->where('mobile', $mobile)
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-2 minutes')))
            ->countAllResults();

        if ($recentOtpsCount >= 3) {
            // Block for 10 minutes
            $otpModel->insert([
                'mobile'            => $mobile,
                'otp'               => null,
                'expires_at'        => null,
                'otp_blocked_until' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
            ]);

            return $this->respond([
                'status' => 429,
                'message' => 'Too many OTPs. You are blocked for 10 minutes.'
            ], 429);
        }

        // ✅ Generate & Save OTP
        $otp = rand(1000, 9999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $otpModel->insert([
            'mobile'     => $mobile,
            'otp'        => $otp,
            'expires_at' => $expiresAt
        ]);

        // TODO: Send OTP using SMS API (Twilio, MSG91, etc.)

        return $this->respond([
            'status'  => 200,
            'message' => 'OTP sent successfully',
            'otp'     => $otp // ⚠️ Only for testing, hide in production
        ], 200);
    }

    public function verifyOtp()
    {
        $mobile = $this->request->getPost('mobile');
        $otp    = $this->request->getPost('otp');

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
}
