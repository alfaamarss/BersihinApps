<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use App\Support\ApiResponse;

class AuthController extends Controller
{
    use ApiResponse;

    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6','confirmed'],
            'device_name' => ['nullable','string'],
        ]);

        unset($data['password_confirmation']);

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = bcrypt($data['password']);
        // Email belum verified, akan diverifikasi melalui email
        $user->email_verified_at = null;
        $user->save();

        // Optional: assign default role
        if (method_exists($user, 'assignRole')) {
            try { $user->assignRole('warga'); } catch (\Throwable $e) { /* ignore if role missing */ }
        }

        // Generate email verification URL
        $verificationUrl = $this->generateVerificationUrl($user);
        
        // Send verification email
        try {
            $this->emailService->sendEmailVerification($user->email, $verificationUrl);
        } catch (\Exception $e) {
            // Log error tapi tetap return success karena user sudah berhasil dibuat
            \Log::error('Failed to send verification email during registration', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        $tokenName = $data['device_name'] ?? 'mobile';
        $token = $user->createToken($tokenName)->plainTextToken;

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => false,
            ],
            'message' => 'Registration successful. Please check your email to verify your account.',
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
            'device_name' => ['nullable','string'],
        ]);

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            return $this->fail('Invalid credentials', 401);
        }

        /** @var User $user */
        $user = User::where('email', $credentials['email'])->firstOrFail();

        $tokenName = $credentials['device_name'] ?? 'mobile';
        $token = $user->createToken($tokenName)->plainTextToken;

        // Get user roles
        $roles = [];
        if (method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames()->toArray();
        }

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }
        return $this->ok(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        // Get user roles
        $roles = [];
        if ($user && method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames()->toArray();
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $roles,
        ];

        return $this->ok($userData);
    }

    /**
     * Generate email verification URL
     */
    private function generateVerificationUrl(User $user): string
    {
        // Generate signed URL for email verification
        $url = URL::temporarySignedRoute(
            'mobile.verification.verify',
            now()->addHours(24), // Valid for 24 hours
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        // Fix URL untuk local development (tambahkan port 8000)
        if (config('app.env') === 'local' && !str_contains($url, ':8000')) {
            $url = str_replace('http://localhost', 'http://localhost:8000', $url);
            $url = str_replace('http://127.0.0.1', 'http://127.0.0.1:8000', $url);
        }

        return $url;
    }
}
