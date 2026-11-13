<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        if (! Schema::hasTable('account_login_links')) {

            Schema::create('account_login_links', function (Blueprint $t) {

                $t->id();

                $t->unsignedBigInteger('user_id')->index();

                $t->string('token_hash', 64)->unique();

                $t->timestamp('expires_at')->nullable();

                $t->timestamp('used_at')->nullable();

                $t->string('ip', 45)->nullable();

                $t->timestamp('created_at')->nullable();

            });

        }

    }



    public function down(): void

    {

        Schema::dropIfExists('account_login_links');

    }

};

