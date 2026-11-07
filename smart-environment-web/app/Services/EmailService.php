<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailService
{
    private string $apiUrl = 'https://citiasia-mail-sender.laravel.cloud/api/send-mail';
    private string $application = 'smart-environment';

    /**
     * Send email verification
     */
    public function sendEmailVerification(string $email, string $verificationUrl): bool
    {
        try {
            $message = view('emails.verify-email', [
                'url' => $verificationUrl
            ])->render();

            $response = Http::post($this->apiUrl, [
                'application' => $this->application,
                'type' => 'register',
                'subject' => 'Verifikasi Email - Smart Environment',
                'message' => $message,
                'to' => $email,
            ]);

            Log::info('Email verification sent', [
                'email' => $email,
                'status' => $response->status(),
                'response_body' => $response->json(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send email verification: ' . $e->getMessage(), [
                'email' => $email,
            ]);
            return false;
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset(string $email, string $resetUrl, string $token): bool
    {
        try {
            $message = view('emails.reset-password', [
                'url' => $resetUrl,
                'token' => $token,
            ])->render();

            $response = Http::post($this->apiUrl, [
                'application' => $this->application,
                'type' => 'reset-password',
                'subject' => 'Reset Password - Smart Environment',
                'message' => $message,
                'to' => $email,
            ]);

            Log::info('Password reset email sent', [
                'email' => $email,
                'status' => $response->status(),
                'response_body' => $response->json(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email: ' . $e->getMessage(), [
                'email' => $email,
            ]);
            return false;
        }
    }

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail(string $email, string $name): bool
    {
        try {
            $message = view('emails.welcome', [
                'name' => $name,
            ])->render();

            $response = Http::post($this->apiUrl, [
                'application' => $this->application,
                'type' => 'welcome',
                'subject' => 'Selamat Datang di Smart Environment',
                'message' => $message,
                'to' => $email,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage());
            return false;
        }
    }
}
