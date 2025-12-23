<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('invitation_code_max_uses')->default(10);
            $table->integer('invitation_code_uses_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('invitation_code_max_uses');
            $table->dropColumn('invitation_code_uses_count');
        });
    }
};
