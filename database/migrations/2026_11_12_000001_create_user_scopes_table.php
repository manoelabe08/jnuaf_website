
<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        Schema::table('territories', function (Blueprint $table) {

            if (!Schema::hasColumn('territories', 'continent')) {

                $table->string('continent', 50)->nullable()->after('country');

            }

        });

        if (!Schema::hasTable('user_scopes')) {

            Schema::create('user_scopes', function (Blueprint $table) {

                $table->id();

                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

                $table->string('scope_type', 20); // global, continent, country, city

                $table->unsignedBigInteger('scope_id')->nullable();

                $table->string('role', 50); // member, pole_lead, city_ref, city_coord, admin

                $table->timestamps();

                $table->unique(['user_id', 'scope_type', 'scope_id', 'role'], 'user_scopes_unique');

            });

        }

    }

    public function down(): void

    {

        Schema::dropIfExists('user_scopes');

        if (Schema::hasColumn('territories', 'continent')) {

            Schema::table('territories', function (Blueprint $table) { $table->dropColumn('continent'); });

        }

    }

};

