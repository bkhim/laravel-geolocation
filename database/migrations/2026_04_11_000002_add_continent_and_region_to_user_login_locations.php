<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_login_locations')) {
            return;
        }

        Schema::table('user_login_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('user_login_locations', 'continent_code')) {
                $table->char('continent_code', 2)->nullable()->after('country_code');
            }

            if (! Schema::hasColumn('user_login_locations', 'region')) {
                $table->string('region')->nullable()->after('city');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_login_locations')) {
            return;
        }

        Schema::table('user_login_locations', function (Blueprint $table) {
            $dropped = [];

            if (Schema::hasColumn('user_login_locations', 'continent_code')) {
                $table->dropColumn('continent_code');
            }

            if (Schema::hasColumn('user_login_locations', 'region')) {
                $table->dropColumn('region');
            }
        });
    }
};
