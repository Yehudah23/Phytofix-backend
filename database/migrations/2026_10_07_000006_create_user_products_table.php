<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_email');
            $table->string('product_id');
            $table->string('product_name');
            $table->string('product_type');
            $table->string('product_image')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->unsignedInteger('current_day')->default(1);
            $table->unsignedInteger('total_days')->default(30);
            $table->string('routine_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_products');
    }
};