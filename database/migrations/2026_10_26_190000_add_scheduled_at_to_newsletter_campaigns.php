<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        if (Schema::hasColumn('newsletter_campaigns', 'scheduled_at')) {

            return;

        }

        Schema::table('newsletter_campaigns', function (Blueprint $table) {

            $table->timestamp('scheduled_at')->nullable()->after('queued_at');

            $table->index(['status', 'scheduled_at'], 'newsletter_campaigns_schedule_index');

        });

    }



    public function down(): void

    {

        Schema::table('newsletter_campaigns', function (Blueprint $table) {

            $table->dropIndex('newsletter_campaigns_schedule_index');

            $table->dropColumn('scheduled_at');

        });

    }

};

