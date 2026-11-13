<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        Schema::table('users', function (Blueprint $t) {

            if (!Schema::hasColumn('users', 'anonymize_after')) {

                $t->timestamp('anonymize_after')->nullable()->index();

            }

        });

    }



    public function down(): void

    {

        Schema::table('users', function (Blueprint $t) {

            if (Schema::hasColumn('users', 'anonymize_after')) {

                $t->dropColumn('anonymize_after');

            }

        });

    }

};

