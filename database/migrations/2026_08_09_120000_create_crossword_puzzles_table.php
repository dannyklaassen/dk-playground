<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crossword_puzzles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('group');
            $table->unsignedTinyInteger('level');
            $table->string('title')->nullable();
            $table->unsignedBigInteger('seed')->nullable();
            $table->unsignedTinyInteger('grid_rows')->nullable();
            $table->unsignedTinyInteger('grid_cols')->nullable();
            $table->json('candidates')->nullable();
            $table->json('entries')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }
};
