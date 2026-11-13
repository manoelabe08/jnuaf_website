<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;



class ChatMessage extends Model

{

    public $timestamps = false;



    protected $fillable = ['room_id', 'user_id', 'kind', 'body', 'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size'];



    protected function casts(): array

    {

        return [

            'body' => 'encrypted',

            'created_at' => 'datetime',

        ];

    }



    public function room(): BelongsTo

    {

        return $this->belongsTo(ChatRoom::class, 'room_id');

    }



    public function user(): BelongsTo

    {

        return $this->belongsTo(User::class);

    }

}

