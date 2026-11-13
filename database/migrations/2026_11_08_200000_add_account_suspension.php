
<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        Schema::table('users', function (Blueprint $table) {

            if (! Schema::hasColumn('users', 'suspended_at')) {

                $table->timestamp('suspended_at')->nullable();

            }

            if (! Schema::hasColumn('users', 'deletion_requested_by')) {

                $table->unsignedBigInteger('deletion_requested_by')->nullable();

            }

            if (! Schema::hasColumn('users', 'deletion_requested_at')) {

                $table->timestamp('deletion_requested_at')->nullable();

            }

        });

        $actions = (new \App\Models\SensitiveAction)->getTable();

        if (! Schema::hasColumn($actions, 'payload')) {

            Schema::table($actions, function (Blueprint $table) {

                $table->text('payload')->nullable();

            });

        }

    }



    public function down(): void

    {

        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn(['suspended_at', 'deletion_requested_by', 'deletion_requested_at']);

        });

        $actions = (new \App\Models\SensitiveAction)->getTable();

        Schema::table($actions, function (Blueprint $table) {

            $table->dropColumn('payload');

        });

    }

};

