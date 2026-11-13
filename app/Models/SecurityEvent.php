<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;



class SecurityEvent extends Model

{

    protected $fillable = ['user_id', 'action', 'details', 'ip', 'user_agent'];



    protected $casts = ['details' => 'array'];



    public function user(): BelongsTo

    {

        return $this->belongsTo(User::class);

    }

}

