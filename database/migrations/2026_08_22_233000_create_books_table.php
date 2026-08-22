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
            $table->id();
            $table->string('google_volume_id')->unique();
            $table->string('isbn_13')->nullable()->index();
            $table->string('title');
            $table->json('authors');
            $table->text('description')->nullable();
            $table->string('cover_url', 2048)->nullable();
            $table->string('language', 16)->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
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
