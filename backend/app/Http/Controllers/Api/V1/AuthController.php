<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Autentikasi API berbasis Sanctum Bearer Token.
 *
 * - POST /api/v1/auth/login  : verifikasi kredensial, tolak akun nonaktif, beri token.
 * - GET  /api/v1/auth/me     : profil + roles + permissions (untuk gating UI).
 * - POST /api/v1/auth/logout : revoke token aktif.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            // 422 dengan format yang sama seperti Error validasi Laravel.
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.',
            ], 403);
        }

        // Revoke token lama device sama supaya tidak menumpuk (satu device satu token).
        $deviceName = $credentials['device_name'] ?? 'browser';
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Berhasil masuk.',
            'data' => [
                'token' => $token,
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil dimuat.',
            'data' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil keluar.',
            'data' => null,
        ]);
    }

    /**
     * Payload user dengan roles & daftar permission untuk gating UI di frontend.
     * student/lecturer terisi bila relasinya ada (future-proof untuk Phase 3+).
     */
    private function userPayload(User $user): array
    {
        $user->loadMissing('roles', 'student', 'lecturer');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar_url,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'student' => $user->student ? [
                'id' => $user->student->id,
                'student_number' => $user->student->student_number,
                'name' => $user->student->name,
                'study_program_id' => $user->student->study_program_id,
            ] : null,
            'lecturer' => $user->lecturer ? [
                'id' => $user->lecturer->id,
                'lecturer_number' => $user->lecturer->lecturer_number,
                'name' => $user->lecturer->name,
                'study_program_id' => $user->lecturer->study_program_id,
            ] : null,
        ];
    }
}
