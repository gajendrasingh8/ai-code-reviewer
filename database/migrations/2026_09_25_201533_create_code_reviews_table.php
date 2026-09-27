<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->longText('code_snippet');
            $table->enum('language', ['php', 'laravel', 'javascript', 'python']);
            $table->integer('quality_score');
            $table->json('issues');
            $table->string('ip_hash')->nullable();
            $table->timestamps();

            $table->index('language');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_reviews');
    }
};
