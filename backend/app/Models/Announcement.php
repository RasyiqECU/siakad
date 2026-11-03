<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Entitas: pengumuman.
 */
class Announcement extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'created_by', 'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targets()
    {
        return $this->hasMany(AnnouncementTarget::class);
    }
}
