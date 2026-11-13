<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class KnownDevice extends Model

{

    protected $fillable = ['user_id', 'signature', 'label', 'last_ip', 'first_seen_at', 'last_seen_at'];



    protected function casts(): array

    {

        return ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime'];

    }

}

