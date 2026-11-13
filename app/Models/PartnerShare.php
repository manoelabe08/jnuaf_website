<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class PartnerShare extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['token_hash', 'code_hash'];
    protected function casts(): array
    {
        return ['generation' => 'integer', 'code_count' => 'integer', 'failed_attempts' => 'integer'] + array_fill_keys([
            'expires_at', 'invited_at', 'first_opened_at', 'last_login_at',
            'code_expires_at', 'next_code_at',
        ], 'datetime');
    }
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(PartnerDocument::class, 'partner_document_share');
    }
}
