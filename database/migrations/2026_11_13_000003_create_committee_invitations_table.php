<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        if (! Schema::hasTable('committee_invitations')) {

            Schema::create('committee_invitations', function (Blueprint $table) {

                $table->id();

                $table->string('email', 190)->unique();

                $table->unsignedBigInteger('invited_by')->nullable();

                $table->timestamp('sent_at')->nullable()->index();

                $table->timestamps();

            });

        }

    }



    public function down(): void

    {

        Schema::dropIfExists('committee_invitations');

    }

};

