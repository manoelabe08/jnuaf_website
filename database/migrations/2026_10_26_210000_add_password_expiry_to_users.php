<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        if (! Schema::hasColumn('users', 'password_expires_at')) {

            Schema::table('users', function (Blueprint $table) {

                $table->timestamp('password_expires_at')->nullable();

                $table->timestamp('expiry_reminded_at')->nullable();

            });

        }



        DB::table('users')->where('must_change_password', true)->whereNull('password_expires_at')

            ->update(['password_expires_at' => now()->addDays(7)]);

    }



    public function down(): void

    {

        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn(['password_expires_at', 'expiry_reminded_at']);

        });

    }

};

