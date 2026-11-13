<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        if (Schema::hasTable('scheduled_mails')) {

            return;

        }



        Schema::create('scheduled_mails', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->longText('recipients');

            $table->boolean('individual')->default(false);

            $table->string('subject', 150);

            $table->longText('body');

            $table->json('attachments')->nullable();

            $table->string('status', 16)->default('scheduled');

            $table->timestamp('scheduled_at');

            $table->timestamp('sent_at')->nullable();

            $table->text('error')->nullable();

            $table->timestamps();

            $table->index(['status', 'scheduled_at']);

        });

    }



    public function down(): void

    {

        Schema::dropIfExists('scheduled_mails');

    }

};

