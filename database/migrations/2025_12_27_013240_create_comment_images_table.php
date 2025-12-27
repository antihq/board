<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->index();
            $table->foreignId('user_id')->index();
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_images');
    }
};
