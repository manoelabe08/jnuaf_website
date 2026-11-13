<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        Schema::table('user_scopes', function (Blueprint $table) {

            if (! Schema::hasColumn('user_scopes', 'scope_value')) {

                $table->string('scope_value', 100)->nullable()->after('scope_id');

            }

        });

        DB::table('territories')->whereIn('code', ['ABJ', 'BYK', 'DKR', 'CAS'])->update(['continent' => 'Afrique']);

        DB::table('territories')->whereIn('code', ['PAR', 'DIJ', 'ANG', 'BRE', 'NRT', 'NTE'])->update(['continent' => 'Europe']);

    }



    public function down(): void

    {

        Schema::table('user_scopes', function (Blueprint $table) {

            if (Schema::hasColumn('user_scopes', 'scope_value')) {

                $table->dropColumn('scope_value');

            }

        });

    }

};

