<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('invitation_code')->nullable();
        });

        // Generate invitation codes for existing teams
        DB::table('teams')->whereNull('invitation_code')->get()->each(function ($team) {
            DB::table('teams')
                ->where('id', $team->id)
                ->update(['invitation_code' => Str::random(8)]);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('invitation_code');
        });
    }
};
