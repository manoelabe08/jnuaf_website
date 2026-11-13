<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model

{

    public const TYPES = ['jnuaf' => 'JNUAF', 'formation' => 'Formation', 'rencontre' => 'Rencontre', 'autre' => 'Autre'];

    protected $fillable = ['title', 'slug', 'type', 'territory_id', 'location', 'starts_at', 'ends_at', 'capacity', 'registration_open', 'is_published', 'description'];

    protected function casts(): array

    {

        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'registration_open' => 'boolean', 'is_published' => 'boolean'];

    }

    public function territory(): BelongsTo

    {

        return $this->belongsTo(Territory::class);

    }

    public function registrations(): HasMany

    {

        return $this->hasMany(Registration::class);

    }

    public function typeLabel(): string

    {

        return self::TYPES[$this->type] ?? (string) $this->type;

    }

}

