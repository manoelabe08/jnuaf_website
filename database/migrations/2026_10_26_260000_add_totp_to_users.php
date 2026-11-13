<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'totp_secret')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->text('totp_secret')->nullable();
            $table->timestamp('totp_enabled_at')->nullable();
            $table->unsignedBigInteger('totp_last_step')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['totp_secret', 'totp_enabled_at', 'totp_last_step']);
        });
    }
};
