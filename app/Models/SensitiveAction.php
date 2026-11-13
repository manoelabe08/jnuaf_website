<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;



class SensitiveAction extends Model

{

    protected $guarded = [];



    protected function casts(): array

    {

        return ['decided_at' => 'datetime', 'consumed_at' => 'datetime', 'expires_at' => 'datetime'];

    }



    public function user(): BelongsTo

    {

        return $this->belongsTo(User::class);

    }



    public function decider(): BelongsTo

    {

        return $this->belongsTo(User::class, 'decided_by');

    }

}

