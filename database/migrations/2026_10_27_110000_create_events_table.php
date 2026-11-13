<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void

    {

        Schema::create('events', function (Blueprint $table) {

            $table->id();

            $table->string('title', 150);

            $table->string('slug', 190)->unique();

            $table->string('type', 20)->default('formation');

            $table->unsignedBigInteger('territory_id')->nullable()->index();

            $table->string('location', 190)->nullable();

            $table->dateTime('starts_at');

            $table->dateTime('ends_at')->nullable();

            $table->unsignedInteger('capacity')->nullable();

            $table->boolean('registration_open')->default(true);

            $table->boolean('is_published')->default(false);

            $table->text('description')->nullable();

            $table->timestamps();

        });

        Schema::table('registrations', function (Blueprint $table) {

            $table->unsignedBigInteger('event_id')->nullable()->after('edition')->index();

        });

        $edition = (string) config('jnuaf.edition', '2027');

        $date = (string) config('jnuaf.event_date', '2027-03-08');

        foreach (DB::table('territories')->orderBy('position')->get() as $t) {

            $id = DB::table('events')->insertGetId([

                'title' => 'JNUAF ' . $edition . ' - ' . $t->city,

                'slug' => 'jnuaf-' . $edition . '-' . strtolower($t->code),

                'type' => 'jnuaf',

                'territory_id' => $t->id,

                'starts_at' => $date . ' 09:00:00',

                'registration_open' => true,

                'is_published' => false,

                'created_at' => now(),

                'updated_at' => now(),

            ]);

            DB::table('registrations')->where('edition', $edition)->where('territory_id', $t->id)->update(['event_id' => $id]);

        }

    }

    public function down(): void

    {

        Schema::table('registrations', function (Blueprint $table) {

            $table->dropColumn('event_id');

        });

        Schema::dropIfExists('events');

    }

};

