<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CommitteeApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name', 'email', 'phone', 'country', 'pole',
        'cv_path', 'letter_path', 'consented_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'consented_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Les pièces jointes suivent le cycle de vie de la candidature.
        
static::saved(function (self $application) {

            if ($application->wasChanged(['status', 'grade'])) {

                try {

                    app(\App\Services\CommitteeAccessService::class)->syncApplication((int) $application->id);

                } catch (\Throwable $e) {

                    report($e);

                }

            }

        });



        static::deleted(function (self $application) {

            try {

                app(\App\Services\CommitteeAccessService::class)->syncApplication((int) $application->id);

            } catch (\Throwable $e) {

                report($e);

            }

        });



        static::deleted(function (self $application) {

            Storage::disk('local')->delete([$application->cv_path, $application->letter_path]);
        });
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
