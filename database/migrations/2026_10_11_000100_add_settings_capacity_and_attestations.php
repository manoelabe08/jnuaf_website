<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paramètres modifiables depuis l'administration (date, textes légaux, ouverture des inscriptions…).
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // null = pas de limite de places sur ce territoire.
        Schema::table('territories', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable()->after('country');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->timestamp('attestation_sent_at')->nullable()->after('checked_in_by');
            $table->timestamp('anonymized_at')->nullable()->after('attestation_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', fn (Blueprint $t) => $t->dropColumn(['attestation_sent_at', 'anonymized_at']));
        Schema::table('territories', fn (Blueprint $t) => $t->dropColumn('capacity'));
        Schema::dropIfExists('settings');
    }
};
