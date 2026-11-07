<?php

namespace App\Http\Controllers\API\Mobile\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class EmailVerificationController extends Controller
{
    use ApiResponse;

    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send email verification link
     * 
     * POST /api/v1/mobile/auth/email/resend-verification
     */
    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->error('Email already verified', 400);
        }

        try {
            // Generate signed verification URL
            $verificationUrl = $this->generateVerificationUrl($user);

            // Send email
            $sent = $this->emailService->sendEmailVerification(
                $user->email,
                $verificationUrl
            );

            if (!$sent) {
                return $this->error('Failed to send verification email', 500);
            }

            return $this->ok(null, 'Verification email has been sent.');

        } catch (\Exception $e) {
            return $this->error('Failed to send verification email: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify email with signed URL
     * 
     * GET /api/v1/mobile/auth/email/verify/{id}/{hash}
     */
    public function verify(Request $request)
    {
        try {
            $user = User::findOrFail($request->route('id'));

            // Check if hash matches
            if (!hash_equals(
                (string) $request->route('hash'),
                sha1($user->getEmailForVerification())
            )) {
                return view('auth.email-verification-result', [
                    'success' => false,
                    'message' => 'Link verifikasi tidak valid.',
                ]);
            }

            // Check if signature is valid
            if (!$request->hasValidSignature()) {
                return view('auth.email-verification-result', [
                    'success' => false,
                    'message' => 'Link verifikasi sudah kadaluarsa. Silakan minta link verifikasi baru.',
                ]);
            }

            if ($user->hasVerifiedEmail()) {
                return view('auth.email-verification-result', [
                    'success' => true,
                    'message' => 'Email Anda sudah terverifikasi sebelumnya.',
                    'user' => $user,
                ]);
            }

            $user->markEmailAsVerified();

            return view('auth.email-verification-result', [
                'success' => true,
                'message' => 'Email berhasil diverifikasi! Sekarang Anda bisa menggunakan akun Anda.',
                'user' => $user,
            ]);

        } catch (\Exception $e) {
            return view('auth.email-verification-result', [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Check email verification status
     * 
     * GET /api/v1/mobile/auth/email/verification-status
     */
    public function checkStatus(Request $request)
    {
        $user = $request->user();

        return $this->ok([
            'verified' => $user->hasVerifiedEmail(),
            'email' => $user->email,
            'verified_at' => $user->email_verified_at?->toIso8601String(),
        ]);
    }

    /**
     * Generate signed verification URL
     */
    protected function generateVerificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'mobile.verification.verify',
            now()->addHours(24),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }
}
