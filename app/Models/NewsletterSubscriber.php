<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class NewsletterSubscriber extends Model
{
    protected $table = 'newsletters';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['sources' => 'array', 'consented_at' => 'datetime', 'unsubscribed_at' => 'datetime', 'bounced_at' => 'datetime'];
    }
}
