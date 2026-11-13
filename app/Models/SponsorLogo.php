<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Storage;



class SponsorLogo extends Model

{

    public const CATEGORIES = ['partner' => 'Partenaire', 'sponsor' => 'Sponsor'];



    protected $fillable = ['name', 'url', 'category', 'path', 'mime', 'position', 'is_active'];



    protected function casts(): array

    {

        return ['is_active' => 'boolean', 'position' => 'integer'];

    }



    protected static function booted(): void

    {

        static::deleted(fn (self $logo) => $logo->path && Storage::disk('local')->delete('sponsors/'.$logo->path));

    }



    public function scopeActive(Builder $query): Builder

    {

        return $query->where('is_active', true)->orderBy('position')->orderBy('name');

    }

}

