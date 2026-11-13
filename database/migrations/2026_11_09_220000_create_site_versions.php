<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        if (! Schema::hasTable('site_versions')) {

            Schema::create('site_versions', function (Blueprint $t) {

                $t->id();

                $t->string('page_key', 60)->index();

                $t->string('version', 30);

                $t->string('status', 20)->default('draft')->index();

                $t->json('content')->nullable();

                $t->unsignedBigInteger('created_by')->nullable();

                $t->timestamp('published_at')->nullable();

                $t->timestamps();

                $t->unique(['page_key', 'version']);

            });

        }

    }



    public function down(): void

    {

        Schema::dropIfExists('site_versions');

    }

};

