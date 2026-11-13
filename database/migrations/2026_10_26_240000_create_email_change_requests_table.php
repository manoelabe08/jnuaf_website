<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_change_requests')) {
            return;
        }

        Schema::create('email_change_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('old_email', 255);
            $table->string('new_email', 255);
            $table->string('status', 24)->default('pending_confirmation');
            $table->boolean('requires_approval')->default(false);
            $table->char('confirm_token_hash', 64)->nullable()->unique();
            $table->char('approval_token_hash', 64)->nullable()->unique();
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_change_requests');
    }
};
