<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        Schema::table('newsletters', function (Blueprint $table) {

            if (! Schema::hasColumn('newsletters', 'bounced_at')) {

                $table->timestamp('bounced_at')->nullable()->after('unsubscribed_at');

            }

            if (! Schema::hasColumn('newsletters', 'bounce_reason')) {

                $table->string('bounce_reason', 255)->nullable()->after('bounced_at');

            }

        });



        if (! Schema::hasTable('mail_bounces')) {

            Schema::create('mail_bounces', function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger('mailbox_uid');

                $table->string('email', 254);

                $table->string('kind', 10);

                $table->string('status_code', 12)->nullable();

                $table->string('diagnostic', 255)->nullable();

                $table->string('applied_to', 30)->nullable();

                $table->unsignedBigInteger('applied_id')->nullable();

                $table->timestamps();

                $table->unique(['mailbox_uid', 'email']);

                $table->index(['email', 'kind']);

            });

        }

    }



    public function down(): void

    {

        Schema::dropIfExists('mail_bounces');

        Schema::table('newsletters', function (Blueprint $table) {

            foreach (['bounce_reason', 'bounced_at'] as $column) {

                if (Schema::hasColumn('newsletters', $column)) {

                    $table->dropColumn($column);

                }

            }

        });

    }

};

