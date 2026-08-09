<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Direction;
use App\Enums\PuzzleGroup;
use App\Models\CrosswordPuzzle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrosswordPuzzle>
 */
class CrosswordPuzzleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group' => fake()->randomElement(PuzzleGroup::cases()),
            'level' => fake()->numberBetween(1, 50),
            'title' => fake()->words(2, true),
        ];
    }

    /** A hand-laid 5x6 grid: TIJGER across, IGLO and REGEN crossing it. */
    public function generated(): static
    {
        $entries = [
            ['word' => 'TIJGER', 'clue' => 'Grote gestreepte kat', 'exercise_id' => null, 'row' => 0, 'col' => 0, 'direction' => Direction::Across->value, 'number' => 1],
            ['word' => 'IGLO', 'clue' => 'Huis van sneeuw', 'exercise_id' => null, 'row' => 0, 'col' => 1, 'direction' => Direction::Down->value, 'number' => 2],
            ['word' => 'REGEN', 'clue' => 'Water dat uit de wolken valt', 'exercise_id' => null, 'row' => 0, 'col' => 5, 'direction' => Direction::Down->value, 'number' => 3],
        ];

        $candidates = [
            ...$entries,
            ['word' => 'WOLKEN', 'clue' => 'Ze drijven in de lucht', 'exercise_id' => null],
        ];

        return $this->state(fn (): array => [
            'seed' => 12345,
            'grid_rows' => 5,
            'grid_cols' => 6,
            'candidates' => array_map(
                fn (array $candidate): array => [
                    'word' => $candidate['word'],
                    'clue' => $candidate['clue'],
                    'exercise_id' => $candidate['exercise_id'],
                ],
                $candidates,
            ),
            'entries' => $entries,
            'generated_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'failed_at' => now(),
        ]);
    }
}
