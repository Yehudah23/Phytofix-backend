<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_routines', function (Blueprint $table) {
            $table->id();
            $table->string('routine_id')->unique();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('product_type');
            $table->string('product_image')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('total_days')->default(30);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('routine_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained('product_routines')->cascadeOnDelete();
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
        Schema::dropIfExists('routine_steps');
        Schema::dropIfExists('product_routines');
    }
};