<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'jnuaf.settings';

    /**
     * Correspondance entre les paramètres modifiables et les clés de config/jnuaf.php.
     * Le reste de l'application lit uniquement config('jnuaf.*') : la base surcharge la config au démarrage.
     */
    public const MAP = [
        'event_date' => 'jnuaf.event_date',
        'registrations_open' => 'jnuaf.registrations_open',
        'retention_months' => 'jnuaf.privacy.retention_months',
        'committee_retention_months' => 'jnuaf.privacy.committee_retention_months',
        'notify_email' => 'jnuaf.notify_email',
        'legal_controller' => 'jnuaf.legal.controller',
        'legal_privacy_email' => 'jnuaf.legal.privacy_email',
        'legal_publisher' => 'jnuaf.legal.publisher',
        'legal_director' => 'jnuaf.legal.director',
        'legal_host' => 'jnuaf.legal.host',
    ];

    public static function values(): array
    {
        // Si la table n'existe pas encore, la requête lève une exception : rien n'est mis en cache.
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    /** Applique les valeurs enregistrées par-dessus la configuration. */
    public static function applyToConfig(): void
    {
        try {
            $values = static::values();
        } catch (\Throwable) {
            return; // base pas encore migrée (installation, tests)
        }

        foreach ($values as $key => $value) {
            if (isset(self::MAP[$key]) && $value !== null && $value !== '') {
                config([self::MAP[$key] => $key === 'registrations_open' ? (bool) $value : $value]);
            }
        }
    }

    public static function store(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget(self::CACHE_KEY);
    }
}
