<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        if (! Schema::hasTable('photo_requests')) {

            Schema::create('photo_requests', function (Blueprint $table) {

                $table->id();

                $table->string('email', 190)->index();

                $table->string('name', 190)->nullable();

                $table->char('token_hash', 64)->unique();

                $table->timestamp('expires_at');

                $table->timestamp('used_at')->nullable();

                $table->timestamp('sent_at')->nullable();

                $table->timestamps();

            });

        }

        if (! Schema::hasColumn('committee_applications', 'photo_path')) {

            Schema::table('committee_applications', function (Blueprint $table) {

                $table->string('photo_path', 255)->nullable();

                $table->timestamp('photo_consented_at')->nullable();

            });

        }

    }



    public function down(): void

    {

        Schema::dropIfExists('photo_requests');

        Schema::table('committee_applications', function (Blueprint $table) {

            $table->dropColumn(['photo_path', 'photo_consented_at']);

        });

    }

};

