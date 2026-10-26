<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_posts', function (Blueprint $table) {
            $table->string('image')->nullable();
        });

        Schema::table('team_members', function (Blueprint $table) {
            $table->string('image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('news_posts', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
