<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer audit berbasis event model Eloquent.
 *
 * Mencatat created/updated/deleted pada model yang diamati. nilai `ip_address`
 * TIDAK ditulis di sini karena tidak tersedia pada konteks observer — aksi
 * lewat HTTP yang sensitif sebaiknya memakai App\Services\SimpleAuditLogger
 * agar IP & user agent ikut tercatat.
 *
 * Field sensitif (password, remember_token, api tokens) tidak pernah disimpan
 * ke kolom `properties`.
 */
class AuditLogObserver
{
    /** Field yang tidak boleh tercatat di audit log. */
    private const SENSITIVE_KEYS = ['password', 'remember_token', 'tokens'];

    /** Batas maksimum atribut yang disimpan. */
    private const MAX_PROPERTY_BYTES = 5000;

    public function created(Model $subject): void
    {
        $this->write('created', $subject, $subject->getAttributes());
    }

    public function updated(Model $subject): void
    {
        $this->write('updated', $subject, $subject->getChanges());
    }

    public function deleted(Model $subject): void
    {
        // getAttributes() pada saat deleted = kondisi terakhir sebelum dihapus.
        $this->write('deleted', $subject, $subject->getAttributes());
    }

    public function restored(Model $subject): void
    {
        $this->write('restored', $subject, $subject->getAttributes());
    }

    private function write(string $action, Model $subject, array $properties): void
    {
        $properties = collect($properties)
            ->forget(self::SENSITIVE_KEYS)
            ->all();

        // Observational updates bisa berlipat tanpa konten — lewati bila kosong.
        if ($action === 'updated' && $properties === []) {
            return;
        }

        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'properties' => json_decode(
                json_encode($properties, JSON_UNESCAPED_UNICODE), true
            ),
            'ip_address' => null, // tidak diketahui di konteks observer
            'user_agent' => null,
        ]);
    }
}
