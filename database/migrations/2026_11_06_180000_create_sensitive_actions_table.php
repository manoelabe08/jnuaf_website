<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        Schema::create('sensitive_actions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('action_key', 60);

            $table->string('method', 10);

            $table->string('route_name', 120)->nullable();

            $table->string('fingerprint', 64);

            $table->string('target_url', 500);

            $table->string('status', 20)->default('pending');

            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('decided_at')->nullable();

            $table->timestamp('consumed_at')->nullable();

            $table->timestamp('expires_at');

            $table->string('ip', 45)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'fingerprint', 'status']);

        });

    }



    public function down(): void

    {

        Schema::dropIfExists('sensitive_actions');

    }

};

