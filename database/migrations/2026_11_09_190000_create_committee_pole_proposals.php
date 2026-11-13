<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        if (! Schema::hasTable('committee_pole_proposals')) {

            Schema::create('committee_pole_proposals', function (Blueprint $t) {

                $t->id();

                $t->unsignedBigInteger('committee_application_id')->index();

                $t->string('from_pole', 150)->nullable();

                $t->string('to_pole', 150);

                $t->string('token_hash', 64)->unique();

                $t->string('status', 20)->default('pending')->index();

                $t->unsignedBigInteger('proposed_by')->nullable();

                $t->timestamp('expires_at')->nullable();

                $t->timestamp('answered_at')->nullable();

                $t->timestamps();

            });

        }

        Schema::table('committee_applications', function (Blueprint $t) {

            if (! Schema::hasColumn('committee_applications', 'assigned_pole')) {

                $t->string('assigned_pole', 150)->nullable();

            }

        });

    }



    public function down(): void

    {

        Schema::dropIfExists('committee_pole_proposals');

        Schema::table('committee_applications', function (Blueprint $t) {

            if (Schema::hasColumn('committee_applications', 'assigned_pole')) {

                $t->dropColumn('assigned_pole');

            }

        });

    }

};

