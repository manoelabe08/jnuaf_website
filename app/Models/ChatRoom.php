<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\HasMany;



class ChatRoom extends Model

{

    protected $fillable = ['type', 'territory_id', 'user_a_id', 'user_b_id'];



    public function territory(): BelongsTo

    {

        return $this->belongsTo(Territory::class);

    }



    public function userA(): BelongsTo

    {

        return $this->belongsTo(User::class, 'user_a_id');

    }



    public function userB(): BelongsTo

    {

        return $this->belongsTo(User::class, 'user_b_id');

    }



    public function messages(): HasMany

    {

        return $this->hasMany(ChatMessage::class, 'room_id');

    }

}

