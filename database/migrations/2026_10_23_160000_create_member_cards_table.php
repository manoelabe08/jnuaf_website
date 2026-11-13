<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        Schema::create('member_cards', function (Blueprint $table) {

            $table->id();

            $table->string('edition', 10);

            $table->string('category', 2);

            $table->string('number', 24)->unique();

            $table->unsignedInteger('sequence');

            $table->foreignId('territory_id')->nullable()->constrained()->nullOnDelete();

            $table->string('source_type', 20);

            $table->unsignedBigInteger('source_id');

            $table->string('full_name');

            $table->string('email')->nullable();

            $table->string('role_label', 160);

            $table->string('photo_path')->nullable();

            $table->string('verify_token', 64)->unique();

            $table->date('valid_until');

            $table->timestamp('issued_at');

            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('sent_at')->nullable();

            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->index(['source_type', 'source_id']);

        });

    }



    public function down(): void

    {

        Schema::dropIfExists('member_cards');

    }

};

