<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ProgramItem extends Model
{
    protected $fillable = ['territory_id', 'slot', 'title', 'description', 'kind', 'room', 'position'];

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    /**
     * Programme d'un territoire : ses créneaux propres s'il en a, sinon le programme type commun.
     */
    public static function forTerritory(?int $territoryId): Collection
    {
        if ($territoryId) {
            $own = static::where('territory_id', $territoryId)->orderBy('position')->get();
            if ($own->isNotEmpty()) {
                return $own;
            }
        }

        return static::whereNull('territory_id')->orderBy('position')->get();
    }
}
