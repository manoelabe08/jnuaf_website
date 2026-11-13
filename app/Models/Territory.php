<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Territory extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'city', 'country', 'capacity', 'position', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'capacity' => 'integer'];
    }

    /** Inscriptions confirmées de l'édition en cours (les places occupées). */
    public function confirmedCount(): int
    {
        return $this->registrations()
            ->where('edition', config('jnuaf.edition'))
            ->where('status', RegistrationStatus::Confirmed)
            ->count();
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->confirmedCount() >= $this->capacity;
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function programItems(): HasMany
    {
        return $this->hasMany(ProgramItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position');
    }

    /** « d'Abidjan », « de Bouaké »… pour les libellés du programme. */
    public function cityWithPreposition(): string
    {
        return preg_match('/^[aeiouyàâéèêîôûAEIOUYÀÂÉÈÊÎÔÛ]/u', $this->city)
            ? "d'{$this->city}"
            : "de {$this->city}";
    }
}
