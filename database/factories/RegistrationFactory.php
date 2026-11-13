<?php

namespace Database\Factories;

use App\Enums\Profile;
use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Models\Territory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'edition' => config('jnuaf.edition'),
            'profile' => Profile::Participante,
            'status' => RegistrationStatus::Confirmed,
            'last_name' => $this->faker->lastName(),
            'first_name' => $this->faker->firstName('female'),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+225 07 00 00 00 00',
            'territory_id' => Territory::factory(),
            'city' => $this->faker->city(),
            'consent_data' => true,
            'consent_image' => false,
            'consented_at' => now(),
            'badge_code' => 'JNUAF27-TST-'.Str::upper(Str::random(6)),
            'qr_token' => Str::random(40),
            'manage_token_hash' => Registration::hashToken(Str::random(48)),
        ];
    }
}
