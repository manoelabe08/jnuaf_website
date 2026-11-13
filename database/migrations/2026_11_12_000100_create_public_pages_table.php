
<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        if (! Schema::hasTable('public_pages')) {

            Schema::create('public_pages', function (Blueprint $table) {

                $table->id();

                $table->string('page_key', 40)->unique();

                $table->string('label', 120);

                $table->string('status', 12)->default('draft');

                $table->string('variant', 12)->nullable();

                $table->timestamp('published_at')->nullable();

                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();

            });

        }

        $now = now();

        $rows = [

            ['home', 'Accueil', 'published', 'old'],

            ['editions', 'Éditions', 'draft', null],

            ['register', 'Inscription', 'draft', null],

            ['speakers', 'Intervenantes', 'draft', null],

            ['committee', 'Comité', 'draft', null],

            ['partners', 'Partenaires', 'draft', null],

            ['about', 'À propos', 'draft', null],

            ['faq', 'FAQ', 'draft', null],

            ['legal', 'Mentions légales', 'draft', null],

        ];

        foreach ($rows as [$key, $label, $status, $variant]) {

            DB::table('public_pages')->insertOrIgnore([

                'page_key' => $key, 'label' => $label, 'status' => $status, 'variant' => $variant,

                'created_at' => $now, 'updated_at' => $now,

            ]);

        }

    }



    public function down(): void

    {

        Schema::dropIfExists('public_pages');

    }

};

