<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprehension_exercise_crossword_puzzle', function (Blueprint $table): void {
            $table->id();
            // Explicit short constraint names: the default ones exceed MySQL's 64-character limit.
            $table->foreignUlid('comprehension_exercise_id')
                ->constrained(indexName: 'cec_puzzle_exercise_foreign')
                ->cascadeOnDelete();
            $table->foreignUlid('crossword_puzzle_id')
                ->constrained(indexName: 'cec_puzzle_puzzle_foreign')
                ->cascadeOnDelete();
            $table->unique(['comprehension_exercise_id', 'crossword_puzzle_id'], 'cec_puzzle_unique');
        });
    }
};
