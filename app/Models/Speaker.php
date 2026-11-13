<?php

namespace App\Models;

use App\Services\SpeakerPhotoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Speaker extends Model
{
    protected $fillable = ['name', 'session', 'bio', 'photo_path', 'territory_id', 'is_published', 'position'];

    protected ?string $archivedPhotoSource = null;

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $speaker) {
            $speaker->archivedPhotoSource = app(SpeakerPhotoService::class)->backup($speaker->photo_path);
        });
        static::deleted(function (self $speaker) {
            app(SpeakerPhotoService::class)->removeOriginal($speaker->archivedPhotoSource);
        });
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }
        if (str_starts_with($this->photo_path, 'images/')) {
            return asset($this->photo_path);
        }
        return Storage::disk('public')->url($this->photo_path);
    }

    public function hasLongBio(): bool
    {
        return filled($this->bio) && (mb_strlen(trim($this->bio)) > 110 || str_contains(trim($this->bio), "\n"));
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    }
}
