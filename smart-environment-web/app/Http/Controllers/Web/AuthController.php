<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AppSetting;
use App\Services\EmailService;
use App\Http\Requests\Web\LoginRequest;
use App\Http\Requests\Web\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function showLogin()
    {
        $appSettings = AppSetting::first();
        return view('auth.login', compact('appSettings'));
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('home'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended(route('home'));
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return back()->withErrors(['email' => 'User tidak ditemukan.']);
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

            // Generate reset URL using detected base domain
            $resetUrl = $this->resolveAppBaseUrl() . '/reset-password/' . $token . '?email=' . urlencode($request->email);

            // Send email using EmailService
            $sent = $this->emailService->sendPasswordReset(
                $request->email,
                $resetUrl,
                $token
            );

            if (!$sent) {
                return back()->withErrors(['email' => 'Gagal mengirim email reset password.']);
            }

            return back()->with('status', 'Link reset password telah dikirim ke email Anda.');

        } catch (\Exception $e) {
            \Log::error('Failed to send password reset email', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['email' => 'Terjadi kesalahan. Silakan coba lagi.']);
        }
    }

    public function showResetPassword(string $token, Request $request)
    {
        $email = $request->query('email');
        
        // Validate token exists
        $resetRecord = \DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();
        
        if (!$resetRecord) {
            return view('auth.reset-password-form', [
                'token' => $token,
                'email' => $email,
            ])->with('error', 'Token reset password tidak ditemukan atau sudah kadaluarsa.');
        }
        
        // Check if token is valid (not expired - 24 hours)
        if (now()->diffInHours($resetRecord->created_at) > 24) {
            return view('auth.reset-password-form', [
                'token' => $token,
                'email' => $email,
            ])->with('error', 'Token reset password sudah kadaluarsa. Silakan minta link baru.');
        }
        
        // Check if token matches
        if (!Hash::check($token, $resetRecord->token)) {
            return view('auth.reset-password-form', [
                'token' => $token,
                'email' => $email,
            ])->with('error', 'Token reset password tidak valid.');
        }
        
        return view('auth.reset-password-form', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Get reset token record
        $resetRecord = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return back()->with('error', 'Token reset password tidak ditemukan.');
        }

        // Check if token is expired (24 hours)
        if (now()->diffInHours($resetRecord->created_at) > 24) {
            \DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->with('error', 'Token sudah kadaluarsa. Silakan minta link baru.');
        }

        // Verify token
        if (!Hash::check($request->token, $resetRecord->token)) {
            return back()->with('error', 'Token tidak valid.');
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Delete reset token
        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Redirect to login page with success message
        return redirect()->route('login')->with('success', 'Password berhasil diubah! Silakan login dengan password baru Anda.');
    }

    private function resolveAppBaseUrl(): string
    {
        $currentUrl = rtrim(url('/'), '/');

        if ($this->isPlaceholderUrl($currentUrl)) {
            $fallback = config('app.production_url', config('app.url'));
            return rtrim($fallback, '/');
        }

        return $currentUrl;
    }

    private function isPlaceholderUrl(string $url): bool
    {
        return Str::contains($url, ['localhost', '127.0.0.1']);
    }
}
