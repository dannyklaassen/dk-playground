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

    /**
     * A hand-laid 9x16 grid of ten entries, big enough to carry a solution word:
     * BERGEN takes one square from six different entries. Fourteen of the
     * twenty-four candidates stay off the grid, so a reroll has both room to
     * place its words and a stock to pick a solution word from.
     */
    public function generated(): static
    {
        $entries = [
            ['word' => 'PLANTEN', 'clue' => 'Ze staan groen in de tuin', 'exercise_id' => null, 'row' => 0, 'col' => 3, 'direction' => Direction::Down->value, 'number' => 1],
            ['word' => 'BLIKSEM', 'clue' => 'Felle flits bij onweer', 'exercise_id' => null, 'row' => 1, 'col' => 5, 'direction' => Direction::Down->value, 'number' => 2],
            ['word' => 'ZEBRA', 'clue' => 'Paard met strepen', 'exercise_id' => null, 'row' => 1, 'col' => 15, 'direction' => Direction::Down->value, 'number' => 3],
            ['word' => 'KORAAL', 'clue' => 'Het groeit onder water', 'exercise_id' => null, 'row' => 2, 'col' => 0, 'direction' => Direction::Across->value, 'number' => 4],
            ['word' => 'WOLKEN', 'clue' => 'Ze drijven in de lucht', 'exercise_id' => null, 'row' => 2, 'col' => 8, 'direction' => Direction::Down->value, 'number' => 5],
            ['word' => 'IGLO', 'clue' => 'Huis van sneeuw', 'exercise_id' => null, 'row' => 3, 'col' => 5, 'direction' => Direction::Across->value, 'number' => 6],
            ['word' => 'RIVIER', 'clue' => 'Stromend water door het land', 'exercise_id' => null, 'row' => 4, 'col' => 10, 'direction' => Direction::Across->value, 'number' => 7],
            ['word' => 'REGEN', 'clue' => 'Water dat uit de wolken valt', 'exercise_id' => null, 'row' => 4, 'col' => 10, 'direction' => Direction::Down->value, 'number' => 7],
            ['word' => 'VOGELS', 'clue' => 'Ze vliegen en zingen', 'exercise_id' => null, 'row' => 5, 'col' => 0, 'direction' => Direction::Across->value, 'number' => 8],
            ['word' => 'SNEEUW', 'clue' => 'Witte vlokken in de winter', 'exercise_id' => null, 'row' => 7, 'col' => 7, 'direction' => Direction::Across->value, 'number' => 9],
        ];

        $unplaced = [
            ['word' => 'OLIFANT', 'clue' => 'Grijs dier met een slurf'],
            ['word' => 'VLINDER', 'clue' => 'Insect met gekleurde vleugels'],
            ['word' => 'BERGEN', 'clue' => 'Hoge toppen in het landschap'],
            ['word' => 'KIKKER', 'clue' => 'Hij springt en kwaakt'],
            ['word' => 'STRAND', 'clue' => 'Zand aan de zee'],
            ['word' => 'TIJGER', 'clue' => 'Grote gestreepte kat'],
            ['word' => 'TOREN', 'clue' => 'Hoog gebouw met een klok'],
            ['word' => 'STEEN', 'clue' => 'Hard stuk van een rots'],
            ['word' => 'BOOM', 'clue' => 'Hij heeft takken en bladeren'],
            ['word' => 'ROOS', 'clue' => 'Bloem met doorns'],
            ['word' => 'NEST', 'clue' => 'Huis van een vogel'],
            ['word' => 'KOOL', 'clue' => 'Groente met dikke bladeren'],
            ['word' => 'RIET', 'clue' => 'Het groeit langs de sloot'],
            ['word' => 'GEIT', 'clue' => 'Dier met horens dat mekkert'],
        ];

        return $this->state(fn (): array => [
            'group' => PuzzleGroup::Group6,
            'seed' => 12345,
            'grid_rows' => 9,
            'grid_cols' => 16,
            'candidates' => array_map(
                fn (array $candidate): array => [
                    'word' => $candidate['word'],
                    'clue' => $candidate['clue'],
                    'exercise_id' => $candidate['exercise_id'] ?? null,
                ],
                [...$entries, ...$unplaced],
            ),
            'entries' => $entries,
            'solution_word' => [
                'word' => 'BERGEN',
                'clue' => 'Hoge toppen in het landschap',
                'cells' => [
                    ['row' => 3, 'col' => 15],
                    ['row' => 4, 'col' => 14],
                    ['row' => 2, 'col' => 2],
                    ['row' => 3, 'col' => 6],
                    ['row' => 5, 'col' => 10],
                    ['row' => 3, 'col' => 3],
                ],
            ],
            'generated_at' => now(),
        ]);
    }

    /** A fully generated puzzle for which no candidate could be matched. */
    public function withoutSolutionWord(): static
    {
        return $this->generated()->state(fn (): array => [
            'solution_word' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'failed_at' => now(),
        ]);
    }
}
