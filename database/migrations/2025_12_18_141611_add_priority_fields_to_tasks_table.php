<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_priority')->default(false);
            $table->timestamp('prioritized_at')->nullable();
            $table->unsignedBigInteger('prioritized_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['is_priority', 'prioritized_at', 'prioritized_by']);
        });
    }
};
