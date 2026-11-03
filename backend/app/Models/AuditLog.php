<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tabel: audit_logs — pencatatan aktivitas penting sistem.
 * Ditulis oleh App\Observers\AuditLogObserver (created/updated/deleted untuk User,
 * tanpa informasi IP) dan App\Services\SimpleAuditLogger (aksi HTTP sensitif, termasuk IP).
 */
class AuditLog extends Model
{
    /** Hanya created_at yang tersimpan (lihat DATABASE_DESIGN §3.1). */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id',
        'properties', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Nama tampilan subjek tanpa semua namespace (mis. "User"). */
    public function getSubjectLabelAttribute(): string
    {
        return class_basename($this->subject_type).'#'.$this->subject_id;
    }
}
