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
        Schema::create('books', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('cover_url')->nullable();
            $table->string('author');
            $table->text('description')->nullable();
            $table->string('isbn')->unique()->nullable();
            $table->string('category');
            $table->integer('stock')->default(0);
            $table->boolean('can_read_online')->default(false);
            $table->string('source')->default('local');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
