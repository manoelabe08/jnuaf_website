<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class PublicPage extends Model

{

    protected $fillable = ['page_key', 'label', 'status', 'variant', 'published_at', 'updated_by'];



    protected $casts = ['published_at' => 'datetime'];

}

