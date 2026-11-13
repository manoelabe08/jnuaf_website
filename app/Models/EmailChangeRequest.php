<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailChangeRequest extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['confirm_token_hash', 'approval_token_hash'];

    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
