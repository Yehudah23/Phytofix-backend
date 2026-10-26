<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescribed_routines', function (Blueprint $table) {
            $table->id();
            $table->string('prescribed_id')->unique();
            $table->string('user_email');
            $table->string('user_name');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('total_days')->default(30);
            $table->unsignedInteger('current_day')->default(1);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('prescribed_routine_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescribed_routine_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('day');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->text('tips')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescribed_routine_steps');
        Schema::dropIfExists('prescribed_routines');
    }
};