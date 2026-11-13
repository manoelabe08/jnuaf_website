<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        if (! Schema::hasColumn('posts', 'cover_path')) {

            Schema::table('posts', function (Blueprint $t) {

                $t->string('cover_path')->nullable();

                $t->string('category', 80)->nullable()->index();

                $t->string('tags', 500)->nullable();

            });

        }

    }



    public function down(): void

    {

        if (Schema::hasColumn('posts', 'cover_path')) {

            Schema::table('posts', function (Blueprint $t) {

                $t->dropColumn(['cover_path', 'category', 'tags']);

            });

        }

    }

};

