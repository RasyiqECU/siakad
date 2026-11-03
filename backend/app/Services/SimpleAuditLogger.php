<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Audit logger sederhana untuk aksi HTTP yang sensitif
 * (user create/update/delete, password reset, perubahan role, update profil).
 *
 * Merekam ip_address dan user_agent dari request aktif — sesuatu yang tidak
 * diketahui oleh App\Observers\AuditLogObserver.
 */
class SimpleAuditLogger
{
    /** Field yang tidak boleh tercatat. */
    private const SENSITIVE_KEYS = ['password', 'remember_token', 'tokens', 'current_password'];

    public static function log(string $action, Model $subject, array $properties = []): AuditLog
    {
        $request = app(Request::class);
        $actor = $request->user();

        $properties = collect($properties)->forget(self::SENSITIVE_KEYS)->all();

        return AuditLog::query()->create([
            'user_id' => $actor?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'properties' => $properties,
            'ip_address' => $request->ip(),
            'user_agent' => self::truncateUserAgent($request),
        ]);
    }

    private static function truncateUserAgent(Request $request): ?string
    {
        $agent = $request->userAgent();

        return $agent === null ? null : mb_substr($agent, 0, 500);
    }
}
