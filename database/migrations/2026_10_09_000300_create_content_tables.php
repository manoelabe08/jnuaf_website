<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_items', function (Blueprint $table) {
            $table->id();
            // null = programme type commun à tous les territoires.
            $table->foreignId('territory_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slot', 40);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('kind', 30);
            $table->string('room')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('speakers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('session');
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('territory_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->string('edition', 20)->index();
            $table->string('title');
            $table->string('path')->nullable(); // null = emplacement en attente de photo
            $table->string('size', 10)->nullable(); // wide | tall
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->json('filters')->nullable();
            $table->unsignedInteger('row_count');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_logs');
        Schema::dropIfExists('gallery_photos');
        Schema::dropIfExists('speakers');
        Schema::dropIfExists('program_items');
    }
};
