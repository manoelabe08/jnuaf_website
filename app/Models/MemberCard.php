<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;



class MemberCard extends Model

{

    protected $fillable = [

        'edition', 'category', 'number', 'sequence', 'territory_id',

        'source_type', 'source_id', 'full_name', 'email', 'role_label',

        'photo_path', 'verify_token', 'valid_until', 'issued_at', 'issued_by',

        'sent_at', 'revoked_at',

    ];



    protected $hidden = ['verify_token'];



    protected function casts(): array

    {

        return [

            'valid_until' => 'date',

            'issued_at' => 'datetime',

            'sent_at' => 'datetime',

            'revoked_at' => 'datetime',
            'anonymized_at' => 'datetime',

        ];

    }



    public function territory(): BelongsTo

    {

        return $this->belongsTo(Territory::class);

    }



    public function issuer(): BelongsTo

    {

        return $this->belongsTo(User::class, 'issued_by');

    }



    public function isRevoked(): bool

    {

        return $this->revoked_at !== null;

    }



    public function isExpired(): bool

    {

        return $this->valid_until->copy()->endOfDay()->isPast();

    }



    public function isActive(): bool

    {

        return $this->anonymized_at === null && ! $this->isRevoked() && ! $this->isExpired();

    }



    public function statusLabel(): string

    {

        return match (true) {

            $this->anonymized_at !== null => 'Anonymisée',
            $this->isRevoked() => 'Révoquée',

            $this->isExpired() => 'Expirée',

            default => 'Valide',

        };

    }

}

