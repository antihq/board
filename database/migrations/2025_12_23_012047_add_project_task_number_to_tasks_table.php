<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('number')->nullable()->after('project_id');
        });

        $projects = Project::all();

        foreach ($projects as $project) {
            $tasks = $project->tasks()->orderBy('created_at')->get();
            foreach ($tasks as $index => $task) {
                $task->update(['number' => $index + 1]);
            }
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('number')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('number');
        });
    }
};
