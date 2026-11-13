<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        Schema::create('known_devices', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->char('signature', 64);

            $table->string('label', 120);

            $table->string('last_ip', 45)->nullable();

            $table->timestamp('first_seen_at')->nullable();

            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'signature']);

        });

    }



    public function down(): void

    {

        Schema::dropIfExists('known_devices');

    }

};

