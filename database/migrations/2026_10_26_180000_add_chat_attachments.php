<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        Schema::table('chat_messages', function (Blueprint $table) {

            $table->string('attachment_path', 255)->nullable();

            $table->string('attachment_name', 160)->nullable();

            $table->string('attachment_mime', 80)->nullable();

            $table->unsignedInteger('attachment_size')->nullable();

        });

    }



    public function down(): void

    {

        Schema::table('chat_messages', function (Blueprint $table) {

            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size']);

        });

    }

};

