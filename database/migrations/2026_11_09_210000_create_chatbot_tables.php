<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        if (! Schema::hasTable('chatbot_conversations')) {

            Schema::create('chatbot_conversations', function (Blueprint $t) {

                $t->id();

                $t->string('code', 16)->unique();

                $t->string('first_name', 80)->nullable();

                $t->string('email', 190)->nullable();

                $t->string('phone', 32)->nullable();

                $t->string('status', 20)->default('bot')->index();

                $t->string('team', 40)->nullable();

                $t->string('source_url', 255)->nullable();

                $t->string('ip_hash', 64)->nullable();

                $t->unsignedBigInteger('assigned_to')->nullable();

                $t->timestamp('consent_at')->nullable();

                $t->timestamp('waiting_since')->nullable()->index();

                $t->timestamp('last_message_at')->nullable();

                $t->timestamp('closed_at')->nullable();

                $t->timestamp('purge_after')->nullable()->index();

                $t->timestamps();

            });

        }



        if (! Schema::hasTable('chatbot_messages')) {

            Schema::create('chatbot_messages', function (Blueprint $t) {

                $t->id();

                $t->unsignedBigInteger('conversation_id')->index();

                $t->string('sender', 12);

                $t->text('body');

                $t->unsignedBigInteger('faq_id')->nullable();

                $t->timestamp('created_at')->nullable();

            });

        }



        if (! Schema::hasTable('chatbot_faqs')) {

            Schema::create('chatbot_faqs', function (Blueprint $t) {

                $t->id();

                $t->string('category', 60)->nullable();

                $t->string('question', 255);

                $t->text('answer');

                $t->text('keywords')->nullable();

                $t->text('variants')->nullable();

                $t->boolean('active')->default(true)->index();

                $t->unsignedInteger('hits')->default(0);

                $t->unsignedInteger('helpful_yes')->default(0);

                $t->unsignedInteger('helpful_no')->default(0);

                $t->string('source', 20)->default('manual');

                $t->timestamps();

                $t->fullText(['question', 'keywords', 'variants', 'answer']);

            });

        }



        if (! Schema::hasTable('chatbot_options')) {

            Schema::create('chatbot_options', function (Blueprint $t) {

                $t->id();

                $t->string('label', 120);

                $t->string('action', 30)->default('faq');

                $t->unsignedBigInteger('faq_id')->nullable();

                $t->string('target', 255)->nullable();

                $t->unsignedSmallInteger('position')->default(0);

                $t->boolean('active')->default(true);

                $t->timestamps();

            });

        }



        if (! Schema::hasTable('chatbot_teams')) {

            Schema::create('chatbot_teams', function (Blueprint $t) {

                $t->id();

                $t->string('slug', 40)->unique();

                $t->string('label', 80);

                $t->string('whatsapp_number', 20);

                $t->boolean('active')->default(true);

                $t->unsignedSmallInteger('position')->default(0);

                $t->timestamps();

            });

        }



        if (! Schema::hasTable('chatbot_unanswered')) {

            Schema::create('chatbot_unanswered', function (Blueprint $t) {

                $t->id();

                $t->string('normalized', 255)->unique();

                $t->text('sample')->nullable();

                $t->unsignedInteger('occurrences')->default(1);

                $t->string('status', 20)->default('open')->index();

                $t->unsignedBigInteger('faq_id')->nullable();

                $t->timestamp('last_seen_at')->nullable();

                $t->timestamps();

            });

        }



        if (! Schema::hasTable('chatbot_events')) {

            Schema::create('chatbot_events', function (Blueprint $t) {

                $t->id();

                $t->string('type', 30)->index();

                $t->unsignedBigInteger('conversation_id')->nullable();

                $t->string('ref', 80)->nullable();

                $t->timestamp('created_at')->nullable()->index();

            });

        }



        if (DB::table('chatbot_teams')->count() === 0) {

            DB::table('chatbot_teams')->insert([

                ['slug' => 'europe', 'label' => "\u{c9}quipe JNUAF Europe", 'whatsapp_number' => '33746389823', 'active' => 1, 'position' => 1, 'created_at' => now(), 'updated_at' => now()],

                ['slug' => 'afrique', 'label' => "\u{c9}quipe JNUAF Afrique", 'whatsapp_number' => '2250777200873', 'active' => 1, 'position' => 2, 'created_at' => now(), 'updated_at' => now()],

            ]);

        }

    }



    public function down(): void

    {

        foreach (['chatbot_events', 'chatbot_unanswered', 'chatbot_teams', 'chatbot_options', 'chatbot_faqs', 'chatbot_messages', 'chatbot_conversations'] as $table) {

            Schema::dropIfExists($table);

        }

    }

};

