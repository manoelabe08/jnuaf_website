<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        Schema::table('committee_applications', function (Blueprint $table) {

            if (! Schema::hasColumn('committee_applications', 'photo_public_consent_at')) {

                $table->timestamp('photo_public_consent_at')->nullable()->after('photo_consented_at');

            }

        });



        if (! DB::table('public_pages')->where('page_key', 'team')->exists()) {

            $template = DB::table('public_pages')->where('page_key', '!=', 'home')->first();

            $row = $template ? (array) $template : [];

            unset($row['id']);

            $row['page_key'] = 'team';

            $row['label'] = "\u{c9}quipe";

            $row['status'] = 'draft';

            if (array_key_exists('published_at', $row)) {

                $row['published_at'] = null;

            }

            DB::table('public_pages')->insert($row);

        }

    }



    public function down(): void

    {

        DB::table('public_pages')->where('page_key', 'team')->delete();

        Schema::table('committee_applications', function (Blueprint $table) {

            if (Schema::hasColumn('committee_applications', 'photo_public_consent_at')) {

                $table->dropColumn('photo_public_consent_at');

            }

        });

    }

};

