<?php

declare(strict_types=1);

use App\Ai\Agents\CrosswordWordWriter;
use App\Enums\PuzzleGroup;
use App\Jobs\GenerateCrosswordPuzzle;
use App\Models\ComprehensionExercise;
use App\Models\CrosswordPuzzle;

/** @var list<list<string>> Eight distinct source words per exercise. */
const PUZZLE_WORD_SETS = [
    ['tijger', 'iglo', 'regen', 'olifant', 'wolken', 'planten', 'zebra', 'kikker'],
    ['vlinder', 'zandbak', 'koraal', 'strand', 'woestijn', 'gletsjer', 'komeet', 'planeet'],
    ['sneeuw', 'storm', 'bliksem', 'donder', 'rivier', 'bergen', 'bossen', 'vogels'],
];

/**
 * A puzzle with one exercise per word set, whose paragraphs literally contain
 * those words.
 */
function puzzleWithExercises(int $count = 3, PuzzleGroup $group = PuzzleGroup::Group6): CrosswordPuzzle
{
    $puzzle = CrosswordPuzzle::factory()->create(['group' => $group, 'level' => 15]);

    foreach (array_slice(PUZZLE_WORD_SETS, 0, $count) as $index => $words) {
        $puzzle->exercises()->attach(
            ComprehensionExercise::factory()->generated()->create([
                'title' => "Tekst {$index}",
                'paragraphs' => array_map(fn (string $word): string => "Hier staat het woord {$word} in een zin.", $words),
            ]),
        );
    }

    return $puzzle;
}

/**
 * The clue may never name its own answer, so it is numbered instead.
 *
 * @param  list<string>  $words
 * @return array{words: list<array{word: string, clue: string}>}
 */
function agentPayload(array $words): array
{
    return [
        'words' => array_values(array_map(
            fn (string $word, int $index): array => [
                'word' => $word,
                'clue' => 'Aanwijzing nummer '.($index + 1),
            ],
            $words,
            array_keys($words),
        )),
    ];
}

it('calls the agent once per exercise and stores the candidates and the grid', function (): void {
    $prompts = [];

    CrosswordWordWriter::fake(function (string $prompt) use (&$prompts): array {
        $prompts[] = $prompt;

        return agentPayload(PUZZLE_WORD_SETS[count($prompts) - 1]);
    });

    $puzzle = puzzleWithExercises();

    (new GenerateCrosswordPuzzle($puzzle))->handle();

    $puzzle->refresh();

    expect($prompts)->toHaveCount(3)
        ->and($prompts[0])->toContain('Tekst 0')
        ->and($prompts[1])->toContain('Tekst 1')
        ->and($prompts[2])->toContain('Tekst 2');

    expect($puzzle->generated_at)->not->toBeNull()
        ->and($puzzle->failed_at)->toBeNull()
        ->and($puzzle->candidates)->toHaveCount(24)
        ->and($puzzle->entries)->not->toBeEmpty()
        ->and($puzzle->seed)->toBeGreaterThan(0)
        ->and($puzzle->grid_rows)->toBeGreaterThan(0)
        ->and($puzzle->grid_cols)->toBeGreaterThan(0)
        // Every candidate is kept, also the ones that did not make the grid.
        ->and(count($puzzle->entries))->toBeLessThanOrEqual(PuzzleGroup::Group6->wordCount());

    foreach ($puzzle->entries as $entry) {
        expect($entry)->toHaveKeys(['word', 'clue', 'exercise_id', 'row', 'col', 'direction', 'number']);
    }
});

it('marks the puzzle as failed when the candidates cannot be laid out', function (): void {
    // Enough candidates to pass validation, but no two of them share a letter,
    // so only the very first one can ever be placed.
    $words = ['ABCD', 'EFGH', 'IJKL', 'MNOP', 'QRST', 'UVWX'];

    CrosswordWordWriter::fake(array_fill(0, 3, agentPayload($words)));

    $puzzle = CrosswordPuzzle::factory()->create(['group' => PuzzleGroup::Group4, 'level' => 15]);

    foreach (range(1, 3) as $index) {
        $puzzle->exercises()->attach(
            ComprehensionExercise::factory()->generated()->create([
                'title' => "Tekst {$index}",
                'paragraphs' => [implode(' ', $words)],
            ]),
        );
    }

    (new GenerateCrosswordPuzzle($puzzle))->handle();

    $puzzle->refresh();

    expect($puzzle->failed_at)->not->toBeNull()
        ->and($puzzle->generated_at)->toBeNull()
        ->and($puzzle->entries)->toBeNull();
});

it('marks the puzzle as failed when too few candidates survive validation', function (): void {
    // Words that do not occur in any of the source texts are all rejected.
    CrosswordWordWriter::fake(array_fill(0, 3, agentPayload(['dinosaurus', 'raketmotor'])));

    $puzzle = puzzleWithExercises();

    (new GenerateCrosswordPuzzle($puzzle))->handle();

    $puzzle->refresh();

    expect($puzzle->failed_at)->not->toBeNull()
        ->and($puzzle->generated_at)->toBeNull()
        ->and($puzzle->entries)->toBeNull();
});

it('sets failed_at through the failed hook on an unexpected error', function (): void {
    $puzzle = CrosswordPuzzle::factory()->create();

    (new GenerateCrosswordPuzzle($puzzle))->failed(new RuntimeException('API unreachable'));

    expect($puzzle->refresh()->failed_at)->not->toBeNull();
});
