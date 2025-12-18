<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('section_id')->nullable();
            $table->timestamp('section_moved_at')->nullable();
            $table->unsignedBigInteger('section_moved_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['section_id', 'section_moved_at', 'section_moved_by']);
        });
    }
};
