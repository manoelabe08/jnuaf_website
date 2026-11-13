<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        Schema::create('sponsor_logos', function (Blueprint $table) {

            $table->id();

            $table->string('name', 120);

            $table->string('url')->nullable();

            $table->string('category', 20)->default('partner');

            $table->string('path');

            $table->string('mime', 40);

            $table->unsignedSmallInteger('position')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'position']);

        });

    }



    public function down(): void

    {

        Schema::dropIfExists('sponsor_logos');

    }

};

