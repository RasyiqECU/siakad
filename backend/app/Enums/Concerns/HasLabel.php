<?php

namespace App\Enums\Concerns;

/**
 * Trait konversi nilai enum → label ramah UI (Bahasa Indonesia).
 * Setiap enum mendefinisikan konstanta LABELS: [value => label].
 */
trait HasLabel
{
    public function label(): string
    {
        return static::LABELS[$this->value] ?? $this->value;
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return static::LABELS;
    }
}
