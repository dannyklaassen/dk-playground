<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprehension_exercises', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('reading_level');
            $table->string('topic');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('level');
            $table->string('title')->nullable();
            $table->json('paragraphs')->nullable();
            $table->json('questions')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }
};
