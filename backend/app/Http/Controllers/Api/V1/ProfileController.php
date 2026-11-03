<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\RespondsWithEnvelope;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Profil pengguna sendiri (API_SPECIFICATION §3.2).
 * - GET   /profile
 * - PATCH /profile : nama, telepon, avatar, ganti password (wajib current_password).
 */
class ProfileController extends Controller
{
    use RespondsWithEnvelope;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['roles', 'student', 'lecturer']);

        $data = UserResource::make($user)->toArray($request);
        $data['permissions'] = $user->getAllPermissions()->pluck('name')->values();
        $data['student'] = $user->student ? $this->studentPayload($user) : null;
        $data['lecturer'] = $user->lecturer ? $this->lecturerPayload($user) : null;

        return $this->respondSingle($data, 'Profil berhasil dimuat.');
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (array_key_exists('password', $validated) && $validated['password'] !== null) {
            if (! $validated['current_password'] || ! Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Password saat ini tidak cocok.'],
                ]);
            }

            $user->password = $validated['password'];
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('phone', $validated)) {
            $user->phone = $validated['phone'];
        }

        $user->save();

        SimpleAuditLogger::log('profile_updated', $user, [
            'changes' => $user->getChanges(),
        ]);

        $user->loadMissing(['roles', 'student', 'lecturer']);

        $data = UserResource::make($user)->toArray($request);

        return $this->respondSingle($data, 'Profil berhasil diperbarui.');
    }

    private function studentPayload(User $user): array
    {
        $student = $user->student;

        return [
            'id' => $student->id,
            'student_number' => $student->student_number,
            'name' => $student->name,
            'study_program_id' => $student->study_program_id,
        ];
    }

    private function lecturerPayload(User $user): array
    {
        $lecturer = $user->lecturer;

        return [
            'id' => $lecturer->id,
            'lecturer_number' => $lecturer->lecturer_number,
            'name' => $lecturer->name,
            'study_program_id' => $lecturer->study_program_id,
        ];
    }
}
