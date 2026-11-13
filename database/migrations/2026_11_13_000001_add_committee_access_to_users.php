<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        Schema::table('users', function (Blueprint $table) {

            if (! Schema::hasColumn('users', 'access_source')) {

                $table->string('access_source', 20)->default('bureau');

            }

            if (! Schema::hasColumn('users', 'committee_application_id')) {

                $table->unsignedBigInteger('committee_application_id')->nullable()->index();

            }

        });

    }



    public function down(): void

    {

        Schema::table('users', function (Blueprint $table) {

            if (Schema::hasColumn('users', 'committee_application_id')) {

                $table->dropColumn('committee_application_id');

            }

            if (Schema::hasColumn('users', 'access_source')) {

                $table->dropColumn('access_source');

            }

        });

    }

};

