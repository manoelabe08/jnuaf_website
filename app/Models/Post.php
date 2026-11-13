<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class Post extends Model

{

    protected $fillable = ['title', 'slug', 'excerpt', 'content', 'cover_path', 'category', 'tags', 'status', 'published_at', 'created_by'];



    protected $casts = ['published_at' => 'datetime'];



    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished($q)

    {

        return $q->where('status', 'published')->whereNotNull('published_at')->orderByDesc('published_at');

    }

}


// jnuaf-s83