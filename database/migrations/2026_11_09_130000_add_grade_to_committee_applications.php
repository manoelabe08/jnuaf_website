<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        Schema::table('committee_applications', function (Blueprint $t) {

            if (!Schema::hasColumn('committee_applications', 'territory_id')) {

                $t->unsignedBigInteger('territory_id')->nullable()->index();

            }

            if (!Schema::hasColumn('committee_applications', 'grade')) {

                $t->string('grade', 40)->default('member');

            }

            if (!Schema::hasColumn('committee_applications', 'grade_changed_by')) {

                $t->unsignedBigInteger('grade_changed_by')->nullable();

            }

            if (!Schema::hasColumn('committee_applications', 'grade_changed_at')) {

                $t->timestamp('grade_changed_at')->nullable();

            }

        });

    }



    public function down(): void

    {

        Schema::table('committee_applications', function (Blueprint $t) {

            foreach (['territory_id', 'grade', 'grade_changed_by', 'grade_changed_at'] as $c) {

                if (Schema::hasColumn('committee_applications', $c)) {

                    $t->dropColumn($c);

                }

            }

        });

    }

};

