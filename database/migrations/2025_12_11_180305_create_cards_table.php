<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->foreignId('column_id')->nullable();
            $table->integer('position');
            $table->foreignId('user_id');
            $table->timestamp('postponed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['board_id', 'column_id', 'position']);
            $table->index(['board_id', 'postponed_at', 'position']);
            $table->index(['board_id', 'completed_at', 'position']);
            $table->index(['board_id', 'column_id', 'postponed_at', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
