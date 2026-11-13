<?php

namespace App\Models;

use App\Enums\Profile;
use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'edition', 'profile', 'status',
        'last_name', 'first_name', 'email', 'phone', 'territory_id', 'city',
        'organization', 'motivation',
        'skills', 'availability', 'experience',
        'expertise', 'bio', 'topic',
        'accessibility_needs', 'consent_data', 'consent_image', 'consented_at',
        'badge_code', 'qr_token', 'manage_token_hash',
        'event_id',
    ];

    protected $hidden = ['qr_token', 'manage_token_hash'];

    protected function casts(): array
    {
        return [
            'profile' => Profile::class,
            'status' => RegistrationStatus::class,
            'consent_data' => 'boolean',
            'consent_image' => 'boolean',
            'consented_at' => 'datetime',
            'badge_sent_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'attestation_sent_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    public function isEventOnly(): bool
    {
        return $this->event_id !== null && $this->event?->type !== 'jnuaf';
    }

    public function eventDate(): \Illuminate\Support\Carbon
    {
        return $this->event?->starts_at ?? \Illuminate\Support\Carbon::parse(config('jnuaf.event_date'));
    }

    public function eventTitle(): string
    {
        return $this->isEventOnly() ? (string) $this->event->title : 'JNUAF ' . $this->edition;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isCancelled(): bool
    {
        return $this->status === RegistrationStatus::Cancelled;
    }

    public function isConfirmed(): bool
    {
        return $this->status === RegistrationStatus::Confirmed;
    }

    public function isWaitlisted(): bool
    {
        return $this->status === RegistrationStatus::Waitlisted;
    }

    /** L'attestation n'est délivrée qu'aux personnes réellement venues (pointées à l'entrée). */
    public function canGetAttestation(): bool
    {
        return $this->checked_in_at !== null && $this->anonymized_at === null;
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByManageToken(string $token): ?self
    {
        return static::where('manage_token_hash', static::hashToken($token))->first();
    }

    public function scopeCurrentEdition(Builder $query): Builder
    {
        return $query->where('edition', config('jnuaf.edition'));
    }

    /** Restreint aux inscriptions visibles par l'utilisateur (territoire pour une coordination). */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $ids = $user->visibleTerritoryIds();

        return $ids === null ? $query : $query->whereIn('territory_id', $ids);
    }
}
