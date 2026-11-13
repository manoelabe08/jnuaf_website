<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerInquiry extends Model
{
    protected $fillable = [
        'organization', 'contact_name', 'job_title', 'email', 'phone', 'country',
        'types', 'message', 'consented_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'types' => 'array',
            'status' => InquiryStatus::class,
            'consented_at' => 'datetime',
            'handled_at' => 'datetime',
        ];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** Libellés lisibles des formes de partenariat souhaitées. */
    public function typeLabels(): array
    {
        $labels = config('jnuaf.partners.types');

        return array_map(fn ($t) => $labels[$t] ?? $t, $this->types ?? []);
    }
}
