<?php

namespace App\Http\Controllers\API\Mobile\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    use ApiResponse;

    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send password reset link
     * 
     * POST /api/v1/mobile/auth/password/forgot
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->error('User not found', 404);
            }

            // Generate reset token
            $token = Str::random(64);

            // Delete existing tokens for this email
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            // Store new token
            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            // Generate reset URL yang mengarah ke web form
            // Deteksi apakah local development (dengan port 8000)
            $appUrl = config('app.url');
            if (config('app.env') === 'local' && !str_contains($appUrl, ':8000')) {
                $appUrl = str_replace('http://localhost', 'http://localhost:8000', $appUrl);
                $appUrl = str_replace('http://127.0.0.1', 'http://127.0.0.1:8000', $appUrl);
            }
            $resetUrl = $appUrl . '/reset-password/' . $token . '?email=' . urlencode($request->email);

            // Send email
            $sent = $this->emailService->sendPasswordReset(
                $request->email,
                $resetUrl,
                $token
            );

            if (!$sent) {
                return $this->error('Failed to send reset email', 500);
            }

            return $this->ok(null, 'Password reset link has been sent to your email.');

        } catch (\Exception $e) {
            return $this->error('Failed to process password reset: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset password with token
     * 
     * POST /api/v1/mobile/auth/password/reset
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            // Get reset token record
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord) {
                return $this->error('Invalid or expired reset token', 400);
            }

            // Verify token
            if (!Hash::check($request->token, $resetRecord->token)) {
                return $this->error('Invalid reset token', 400);
            }

            // Check if token is not expired (24 hours)
            $createdAt = \Carbon\Carbon::parse($resetRecord->created_at);
            if ($createdAt->addHours(24)->isPast()) {
                DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->delete();
                return $this->error('Reset token has expired', 400);
            }

            // Update password
            $user = User::where('email', $request->email)->first();
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Delete used token
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            // Revoke all tokens for security
            $user->tokens()->delete();

            return $this->ok(null, 'Password has been reset successfully. Please login with your new password.');

        } catch (\Exception $e) {
            return $this->error('Failed to reset password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify reset token (optional - to check if token is valid before showing reset form)
     * 
     * POST /api/v1/mobile/auth/password/verify-token
     */
    public function verifyToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord) {
                return $this->error('Invalid token', 400);
            }

            if (!Hash::check($request->token, $resetRecord->token)) {
                return $this->error('Invalid token', 400);
            }

            $createdAt = \Carbon\Carbon::parse($resetRecord->created_at);
            if ($createdAt->addHours(24)->isPast()) {
                return $this->error('Token has expired', 400);
            }

            return $this->ok([
                'valid' => true,
                'email' => $request->email,
            ], 'Token is valid');

        } catch (\Exception $e) {
            return $this->error('Failed to verify token: ' . $e->getMessage(), 500);
        }
    }
}
