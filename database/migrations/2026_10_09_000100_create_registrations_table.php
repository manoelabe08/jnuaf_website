<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('edition', 10)->index();
            $table->string('profile', 20)->index();
            $table->string('status', 20)->default('confirmed')->index();

            $table->string('last_name');
            $table->string('first_name');
            $table->string('email');
            $table->string('phone', 30);
            $table->foreignId('territory_id')->constrained()->restrictOnDelete();
            $table->string('city');

            // Participante
            $table->string('organization')->nullable();
            $table->text('motivation')->nullable();
            // Bénévole
            $table->string('skills')->nullable();
            $table->string('availability')->nullable();
            $table->text('experience')->nullable();
            // Mentor / intervenante
            $table->string('expertise')->nullable();
            $table->text('bio')->nullable();
            $table->string('topic')->nullable();

            $table->string('accessibility_needs')->nullable();
            $table->boolean('consent_data');
            $table->boolean('consent_image')->default(false);
            $table->timestamp('consented_at');

            // Identifiant lisible imprimé sur le badge.
            $table->string('badge_code', 30)->unique();
            // Jeton opaque encodé dans le QR code : aucune donnée personnelle en clair.
            $table->string('qr_token', 64)->unique();
            // Empreinte SHA-256 du lien personnel de gestion (le jeton brut n'est jamais stocké).
            $table->string('manage_token_hash', 64)->unique();

            $table->timestamp('badge_sent_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['edition', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
