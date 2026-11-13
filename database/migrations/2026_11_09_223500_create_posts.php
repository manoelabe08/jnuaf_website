<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        if (! Schema::hasTable('posts')) {

            Schema::create('posts', function (Blueprint $t) {

                $t->id();

                $t->string('title');

                $t->string('slug')->unique();

                $t->string('excerpt', 500)->nullable();

                $t->longText('content');

                $t->string('status', 20)->default('draft')->index();

                $t->timestamp('published_at')->nullable();

                $t->unsignedBigInteger('created_by')->nullable();

                $t->timestamps();

            });

        }

    }



    public function down(): void

    {

        Schema::dropIfExists('posts');

    }

};

