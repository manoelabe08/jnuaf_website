<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('organization');
            $table->string('contact_name');
            $table->string('job_title')->nullable();
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('country', 60);
            $table->json('types');
            $table->text('message');
            $table->timestamp('consented_at');
            $table->string('status', 20)->default('new')->index();
            $table->text('notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_inquiries');
    }
};
