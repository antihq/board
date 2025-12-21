<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('handle')->nullable()->after('name');
        });

        // Populate handles and make field required
        DB::statement('UPDATE teams SET handle = lower(substr(name, 1, 50)) WHERE handle IS NULL');

        Schema::table('teams', function (Blueprint $table) {
            $table->string('handle')->nullable(false)->change();
            $table->unique('handle');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->dropColumn('handle');
        });
    }
};
