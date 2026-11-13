<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        Schema::create('chat_rooms', function (Blueprint $table) {

            $table->id();

            $table->string('type', 16);

            $table->foreignId('territory_id')->nullable()->unique()->constrained()->cascadeOnDelete();

            $table->foreignId('user_a_id')->nullable()->constrained('users')->cascadeOnDelete();

            $table->foreignId('user_b_id')->nullable()->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_a_id', 'user_b_id']);

        });



        Schema::create('chat_messages', function (Blueprint $table) {

            $table->id();

            $table->foreignId('room_id')->constrained('chat_rooms')->cascadeOnDelete();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('kind', 16)->default('text');

            $table->text('body');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['room_id', 'id']);

        });



        Schema::create('chat_reads', function (Blueprint $table) {

            $table->foreignId('room_id')->constrained('chat_rooms')->cascadeOnDelete();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedBigInteger('last_message_id')->default(0);

            $table->primary(['room_id', 'user_id']);

        });

    }



    public function down(): void

    {

        Schema::dropIfExists('chat_reads');

        Schema::dropIfExists('chat_messages');

        Schema::dropIfExists('chat_rooms');

    }

};

