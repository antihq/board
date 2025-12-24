<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('team_id');
            $table->index('user_id');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->index('project_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index('task_id');
            $table->index('user_id');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->index('team_id');
        });

        Schema::table('tag_task', function (Blueprint $table) {
            $table->index('tag_id');
            $table->index('task_id');
        });

        Schema::table('task_assignments', function (Blueprint $table) {
            $table->index('task_id');
            $table->index('user_id');
        });

        Schema::table('task_subscriptions', function (Blueprint $table) {
            $table->index('task_id');
            $table->index('user_id');
        });

        Schema::table('saved_tasks', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('task_id');
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['team_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['task_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex(['team_id']);
        });

        Schema::table('tag_task', function (Blueprint $table) {
            $table->dropIndex(['tag_id']);
            $table->dropIndex(['task_id']);
        });

        Schema::table('task_assignments', function (Blueprint $table) {
            $table->dropIndex(['task_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('task_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['task_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('saved_tasks', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['task_id']);
            $table->dropIndex(['team_id']);
        });
    }
};
