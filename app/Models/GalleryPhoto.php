<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GalleryPhoto extends Model
{
    protected $fillable = ['edition', 'title', 'path', 'size', 'is_published', 'position'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::deleted(fn (self $p) => $p->path && ! str_starts_with($p->path, 'images/') && Storage::disk('public')->delete($p->path));
    }

    /** Les fichiers téléversés vivent sur le disque public ; les images livrées avec le site sous public/images. */
    public function url(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return str_starts_with($this->path, 'images/') ? asset($this->path) : Storage::disk('public')->url($this->path);
    }
}
