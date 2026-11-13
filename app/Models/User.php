<?php

namespace App\Models;

use App\Enums\Role;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'territory_id'];

    protected $hidden = ['password', 'remember_token', 'two_factor_recovery', 'totp_secret'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_recovery' => 'encrypted:array',
            'totp_secret' => 'encrypted',
            'totp_enabled_at' => 'datetime',
            'password_expires_at' => 'datetime',
            'expiry_reminded_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /** Email de réinitialisation en français, à la charte JNUAF. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    
public const TEMP_PASSWORD_DAYS = 7;



    protected static function booted(): void

    {

        static::saving(function (User $user) {

            if ($user->must_change_password) {

                if ($user->isDirty('must_change_password') || $user->isDirty('password')) {

                    $user->password_expires_at = now()->addDays(self::TEMP_PASSWORD_DAYS);

                    $user->expiry_reminded_at = null;

                }

            } elseif ($user->isDirty('must_change_password')) {

                $user->password_expires_at = null;

                $user->expiry_reminded_at = null;

            }

        });

    }



    
public function avatarUrl(): ?string

    {

        if (! $this->avatar_path) {

            return null;

        }



        return route('admin.profile.avatar', ['user' => $this->id, 'v' => substr(md5($this->avatar_path), 0, 8)]);

    }



    public function hasTotp(): bool
    {
        return ! empty($this->totp_secret) && $this->totp_enabled_at !== null;
    }

    public function territory(): BelongsTo


    {
        return $this->belongsTo(Territory::class);
    }

    
/** Compte issu du comite : valable tant que la candidature liee est retenue avec un grade ouvrant un acces. */

    public function committeeAccessValid(): bool

    {

        if ($this->access_source !== 'committee') {

            return true;

        }

        if (! $this->committee_application_id) {

            return false;

        }

        $application = CommitteeApplication::find($this->committee_application_id);



        return $application !== null

            && $application->status === \App\Enums\ApplicationStatus::Accepted

            && in_array((string) $application->grade, (array) config('jnuaf_access.grades', []), true);

    }



    public function isAdmin(): bool

    {
        return $this->role === Role::Admin;
    }


    /** Compte du Bureau de Pilotage rattache au Gmail de la JNUAF : seul a valider les actions critiques. */

    public function isSuperAdmin(): bool

    {

        $central = mb_strtolower(trim((string) config('jnuaf.notify_email')));



        return $this->isAdmin() && $central !== '' && mb_strtolower((string) $this->email) === $central;

    }




    
private ?array $visibleIdsCache = null;



    private bool $visibleIdsCached = false;



    /**

     * Territoires visibles : null = tous (administrateur). Refus par defaut : aucun rattachement = aucun territoire.

     *

     * @return array<int, int>|null

     */

    public function visibleTerritoryIds(): ?array

    {

        if ($this->isAdmin()) {

            return null;

        }

        if ($this->visibleIdsCached) {

            return $this->visibleIdsCache;

        }



        $ids = $this->territory_id ? [(int) $this->territory_id] : [];

        $scopes = \Illuminate\Support\Facades\DB::table('user_scopes')->where('user_id', $this->id)->get();

        foreach ($scopes as $scope) {

            if ($scope->scope_type === 'city' && $scope->scope_id) {

                $ids[] = (int) $scope->scope_id;

            } elseif ($scope->scope_type === 'country' && $scope->scope_value) {

                $ids = array_merge($ids, Territory::where('country', $scope->scope_value)->pluck('id')->map(fn ($v) => (int) $v)->all());

            } elseif ($scope->scope_type === 'continent' && $scope->scope_value) {

                $ids = array_merge($ids, Territory::where('continent', $scope->scope_value)->pluck('id')->map(fn ($v) => (int) $v)->all());

            }

        }

        $this->visibleIdsCached = true;



        return $this->visibleIdsCache = array_values(array_unique($ids));

    }



    /** Territoire auquel l'utilisateur est restreint (null = tous). */

    public function scopedTerritoryId(): ?int
    {
        return $this->isAdmin() ? null : $this->territory_id;
    }
}
