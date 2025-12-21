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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('handle')->nullable()->after('name');
        });

        // Populate handle for existing projects
        DB::table('projects')->orderBy('id')->chunk(100, function ($projects) {
            foreach ($projects as $project) {
                $baseHandle = Str::slug($project->name);
                $handle = $baseHandle;
                $counter = 1;

                // Ensure handle is unique
                while (DB::table('projects')->where('handle', $handle)->where('id', '!=', $project->id)->exists()) {
                    $handle = $baseHandle . '-' . $counter;
                    $counter++;
                }

                DB::table('projects')->where('id', $project->id)->update(['handle' => $handle]);
            }
        });

        // Make handle column non-null and unique
        Schema::table('projects', function (Blueprint $table) {
            $table->string('handle')->nullable(false)->change();
            $table->unique('handle');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('handle');
        });
    }
};
