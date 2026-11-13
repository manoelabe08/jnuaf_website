<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class NewsletterCampaign extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['sources' => 'array', 'queued_at' => 'datetime', 'scheduled_at' => 'datetime', 'finished_at' => 'datetime', 'web_expires_at' => 'datetime'];
    }
    public function attachments(): HasMany { return $this->hasMany(NewsletterAttachment::class); }
    public function deliveries(): HasMany { return $this->hasMany(NewsletterDelivery::class); }
}
