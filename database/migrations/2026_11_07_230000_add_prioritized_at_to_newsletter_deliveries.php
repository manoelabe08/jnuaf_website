<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        if (Schema::hasColumn('newsletter_deliveries', 'prioritized_at')) {

            return;

        }

        Schema::table('newsletter_deliveries', function (Blueprint $table) {

            $table->timestamp('prioritized_at', 3)->nullable();

            $table->index(['status', 'prioritized_at'], 'nd_status_prio_idx');

        });

    }



    public function down(): void

    {

        Schema::table('newsletter_deliveries', function (Blueprint $table) {

            $table->dropIndex('nd_status_prio_idx');

            $table->dropColumn('prioritized_at');

        });

    }

};

