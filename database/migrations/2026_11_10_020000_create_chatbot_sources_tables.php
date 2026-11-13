<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        if (! Schema::hasTable('chatbot_sources')) {

            Schema::create('chatbot_sources', function (Blueprint $t) {

                $t->id();

                $t->string('type', 10)->index();

                $t->string('title', 255);

                $t->string('url', 500)->nullable();

                $t->string('file_path', 255)->nullable();

                $t->unsignedBigInteger('post_id')->nullable()->index();

                $t->boolean('active')->default(false)->index();

                $t->unsignedInteger('passage_count')->default(0);

                $t->unsignedInteger('char_count')->default(0);

                $t->string('error', 255)->nullable();

                $t->timestamp('fetched_at')->nullable();

                $t->unsignedBigInteger('created_by')->nullable();

                $t->timestamps();

            });

        }

        if (! Schema::hasTable('chatbot_passages')) {

            Schema::create('chatbot_passages', function (Blueprint $t) {

                $t->id();

                $t->unsignedBigInteger('source_id')->index();

                $t->unsignedInteger('position')->default(0);

                $t->unsignedSmallInteger('page')->nullable();

                $t->text('body');

                $t->text('tokens');

                $t->timestamp('created_at')->nullable();

            });

        }

    }



    public function down(): void

    {

        Schema::dropIfExists('chatbot_passages');

        Schema::dropIfExists('chatbot_sources');

    }

};

