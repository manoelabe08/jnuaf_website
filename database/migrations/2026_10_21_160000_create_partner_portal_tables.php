<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('partner_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('stored_name')->unique();
            $table->string('download_name');
            $table->unsignedBigInteger('bytes');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('partner_shares', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->index();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->unsignedInteger('generation')->default(0);
            $table->string('status')->default('draft')->index();
            $table->string('revoked_reason')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedTinyInteger('code_count')->default(0);
            $table->unsignedTinyInteger('failed_attempts')->default(0);
            $table->char('code_hash', 64)->nullable();
            $table->timestamp('code_expires_at')->nullable();
            $table->timestamp('next_code_at')->nullable();
            $table->timestamps();
        });
        Schema::create('partner_document_share', function (Blueprint $table) {
            $table->foreignId('partner_share_id')->constrained('partner_shares')->cascadeOnDelete();
            $table->foreignId('partner_document_id')->constrained('partner_documents')->cascadeOnDelete();
            $table->primary(['partner_share_id', 'partner_document_id']);
        });
        Schema::create('partner_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_share_id')->constrained('partner_shares')->cascadeOnDelete();
            $table->unsignedInteger('generation');
            $table->foreignId('partner_document_id')->nullable()->constrained('partner_documents')->nullOnDelete();
            $table->string('kind');
            $table->text('detail')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['partner_share_id', 'created_at']);
        });
        Schema::create('partner_notices', function (Blueprint $table) {
            $table->id();
            $table->string('unique_key')->unique();
            $table->string('recipient');
            $table->string('subject');
            $table->text('body'); // Chiffré avec APP_KEY.
            $table->unsignedBigInteger('context_share_id')->nullable();
            $table->unsignedInteger('context_generation')->nullable();
            $table->char('context_code_hash', 64)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_notices');
        Schema::dropIfExists('partner_events');
        Schema::dropIfExists('partner_document_share');
        Schema::dropIfExists('partner_shares');
        Schema::dropIfExists('partner_documents');
    }
};
