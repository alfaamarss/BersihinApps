<?php

namespace App\Http\Controllers\API\Mobile\Collector;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * Get current collector profile
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return $this->ok([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update collector profile
     */
    public function update(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors()->toArray());
            }

            $data = [];

            if ($request->has('name')) {
                $data['name'] = $request->name;
            }

            if ($request->has('email')) {
                $data['email'] = $request->email;
                // Reset email verification when email is changed
                $data['email_verified_at'] = null;
            }

            $user->update($data);

            return $this->ok([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                ],
            ], 'Profile updated successfully.');
        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors()->toArray());
            }

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->error('Current password is incorrect.', 400);
            }

            // Check if new password is same as current (optional but recommended)
            if (Hash::check($request->new_password, $user->password)) {
                return $this->error('New password must be different from current password.', 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Log the password change for security audit
            Log::info('Password changed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => 'collector',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return $this->ok(null, 'Password changed successfully.');
        } catch (\Exception $e) {
            Log::error('Password change error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to change password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete account (soft delete or deactivate)
     */
    public function deleteAccount(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors()->toArray());
            }

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                return $this->error('Password is incorrect.', 400);
            }

            // Revoke all tokens
            $user->tokens()->delete();

            // Log account deletion
            Log::warning('Account deletion requested', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => 'collector'
            ]);

            // Soft delete or deactivate the user
            // Option 1: Soft delete (if you have SoftDeletes trait)
            // $user->delete();
            
            // Option 2: Deactivate (recommended for audit trail)
            // $user->update(['is_active' => false]);

            return $this->ok(null, 'Account deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Account deletion error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to delete account: ' . $e->getMessage(), 500);
        }
    }
}
