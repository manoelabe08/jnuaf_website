<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class NewsletterDelivery extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['token_hash', 'token_ciphertext'];
    protected function casts(): array
    {
        return ['processing_at' => 'datetime', 'sent_at' => 'datetime'];
    }
    public function campaign(): BelongsTo { return $this->belongsTo(NewsletterCampaign::class, 'newsletter_campaign_id'); }
    public function subscriber(): BelongsTo { return $this->belongsTo(NewsletterSubscriber::class, 'newsletter_id'); }
}
