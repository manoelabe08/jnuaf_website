<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        if (! Schema::hasColumn('photo_requests', 'replace')) {

            Schema::table('photo_requests', function (Blueprint $table) {

                $table->boolean('replace')->default(false);

                $table->string('reason', 300)->nullable();

            });

        }

        if (! Schema::hasColumn('committee_applications', 'photo_rejected_at')) {

            Schema::table('committee_applications', function (Blueprint $table) {

                $table->timestamp('photo_rejected_at')->nullable();

                $table->string('photo_reject_reason', 300)->nullable();

            });

        }

    }



    public function down(): void

    {

        Schema::table('photo_requests', function (Blueprint $table) {

            $table->dropColumn(['replace', 'reason']);

        });

        Schema::table('committee_applications', function (Blueprint $table) {

            $table->dropColumn(['photo_rejected_at', 'photo_reject_reason']);

        });

    }

};

