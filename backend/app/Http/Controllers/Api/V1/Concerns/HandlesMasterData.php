<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Helper pola CRUD seragam untuk Master Data (API_SPECIFICATION §3.3):
 * - paginasi per_page (default 15, max 100);
 * - filter ?search pada kolom teks;
 * - ?sort_by (whitelist) + ?sort_dir (asc|desc).
 *
 * Menyertakan RespondsWithEnvelope agar cukup satu trait per controller.
 */
trait HandlesMasterData
{
    use RespondsWithEnvelope;

    /** Paginasi seragam: default 15, maksimum 100 item per halaman. */
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return max(1, min((int) ($request->query('per_page', $default)), $max));
    }

    /**
     * Terapkan ?search pada daftar kolom teks.
     * Operator mengikuti driver: ilike (PostgreSQL), like (SQLite dsb.).
     */
    protected function applySearch(Builder $query, ?string $search, array $columns): Builder
    {
        if ($search === null || $search === '' || $columns === []) {
            return $query;
        }

        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $needle = "%{$search}%";

        return $query->where(function (Builder $q) use ($columns, $operator, $needle) {
            foreach ($columns as $column) {
                $q->orWhere($column, $operator, $needle);
            }
        });
    }

    /**
     * Terapkan ?sort_by + ?sort_dir dengan whitelist kolom.
     * Nilai di luar whitelist dikembalikan ke default (aman dari injeksi kolom SQL).
     */
    protected function applySorting(
        Builder $query,
        Request $request,
        array $whitelist,
        string $default,
        string $defaultDirection = 'asc',
    ): Builder {
        $sortBy = (string) $request->query('sort_by', $default);
        if (! in_array($sortBy, $whitelist, true)) {
            $sortBy = $default;
        }

        $sortDir = strtolower((string) $request->query('sort_dir', $defaultDirection));
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = $defaultDirection;
        }

        return $query->orderBy($sortBy, $sortDir);
    }
}
