<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        if (! Schema::hasTable('trash_items')) {

            Schema::create('trash_items', function (Blueprint $table) {

                $table->id();

                $table->string('model', 60);

                $table->unsignedBigInteger('original_id');

                $table->string('label', 190)->nullable();

                $table->longText('payload');

                $table->unsignedBigInteger('deleted_by')->nullable();

                $table->timestamp('deleted_at')->nullable();

                $table->timestamp('purge_at')->nullable()->index();

                $table->timestamp('restored_at')->nullable();

            });

        }

        if (! Schema::hasColumn('users', 'anonymized_at')) {

            Schema::table('users', function (Blueprint $table) {

                $table->timestamp('anonymized_at')->nullable();

            });

        }

    }



    public function down(): void

    {

        Schema::dropIfExists('trash_items');

        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn('anonymized_at');

        });

    }

};

