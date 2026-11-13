<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        if (! Schema::hasColumn('users', 'two_factor_enabled')) {

            Schema::table('users', function (Blueprint $table) {

                $table->boolean('two_factor_enabled')->default(false);

                $table->text('two_factor_recovery')->nullable();

            });

        }



        if (! Schema::hasTable('two_factor_challenges')) {

            Schema::create('two_factor_challenges', function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger('user_id')->index();

                $table->char('code_hash', 64);

                $table->unsignedTinyInteger('attempts')->default(0);

                $table->timestamp('expires_at');

                $table->timestamp('created_at')->nullable();

            });

        }

    }



    public function down(): void

    {

        Schema::dropIfExists('two_factor_challenges');

        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn(['two_factor_enabled', 'two_factor_recovery']);

        });

    }

};

