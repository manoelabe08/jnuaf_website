<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        Schema::table('member_cards', function (Blueprint $t) {

            if (!Schema::hasColumn('member_cards', 'revoked_reason')) {

                $t->string('revoked_reason', 40)->nullable();

            }

        });

    }



    public function down(): void

    {

        Schema::table('member_cards', function (Blueprint $t) {

            if (Schema::hasColumn('member_cards', 'revoked_reason')) {

                $t->dropColumn('revoked_reason');

            }

        });

    }

};

