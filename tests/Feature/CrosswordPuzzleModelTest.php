<?php

declare(strict_types=1);

use App\Enums\Direction;
use App\Models\CrosswordPuzzle;
use App\Support\Crossword\WordCandidate;

it('rebuilds the grid matrix from the stored entries', function (): void {
    $cells = CrosswordPuzzle::factory()->generated()->create()->cells();

    expect($cells)->toHaveCount(9)
        ->and($cells[0])->toHaveCount(16)
        ->and($cells[0][3])->toBe(['letter' => 'P', 'number' => 1, 'solution' => null])
        ->and($cells[2][0])->toBe(['letter' => 'K', 'number' => 4, 'solution' => null])
        ->and($cells[2][1])->toBe(['letter' => 'O', 'number' => null, 'solution' => null])
        // The R of KORAAL carries the third letter of the solution word.
        ->and($cells[2][2])->toBe(['letter' => 'R', 'number' => null, 'solution' => 'c'])
        ->and($cells[3][15])->toBe(['letter' => 'B', 'number' => null, 'solution' => 'a'])
        ->and($cells[0][0])->toBeNull()
        ->and($cells[8][0])->toBeNull();
});

it('keeps the hand-laid fixture and its solution word in step', function (): void {
    $puzzle = CrosswordPuzzle::factory()->generated()->create();

    expect(markedLetters($puzzle))->toBe($puzzle->solutionWord()->word);
});

it('leaves every cell unmarked for a puzzle without a solution word', function (): void {
    $cells = CrosswordPuzzle::factory()->withoutSolutionWord()->create()->cells();

    expect(collect($cells)->flatten(1)->filter()->pluck('solution')->unique()->all())->toBe([null]);
});

it('caps the printed cell size at 12mm and scales it down for a wide grid', function (): void {
    expect(CrosswordPuzzle::factory()->generated()->create(['grid_cols' => 10])->cellSizeMm())->toBe(12.0)
        ->and(CrosswordPuzzle::factory()->generated()->create(['grid_cols' => 20])->cellSizeMm())->toBe(8.5);
});

it('splits the entries by direction, ordered by number', function (): void {
    $puzzle = CrosswordPuzzle::factory()->generated()->create();

    expect(array_column($puzzle->entriesFor(Direction::Across), 'word'))
        ->toBe(['KORAAL', 'IGLO', 'RIVIER', 'VOGELS', 'SNEEUW'])
        ->and(array_column($puzzle->entriesFor(Direction::Down), 'word'))
        ->toBe(['PLANTEN', 'BLIKSEM', 'ZEBRA', 'WOLKEN', 'REGEN']);
});

it('assigns a solution word to a relaid grid, also to a puzzle that had none', function (): void {
    $puzzle = CrosswordPuzzle::factory()->withoutSolutionWord()->create();
    $before = $puzzle->entries;

    // A fixed seed, so the reroll lands on a known grid instead of a random one.
    expect($puzzle->relayout(
        array_map(WordCandidate::fromArray(...), $puzzle->candidates),
        [],
        minimumPlacements: 10,
        seed: 4242,
    ))->toBeTrue();

    $solutionWord = $puzzle->solutionWord();

    expect($puzzle->entries)->not->toBe($before)
        ->and($solutionWord)->not->toBeNull()
        ->and($solutionWord->word)->not->toBeIn(array_column($puzzle->entries, 'word'))
        ->and(markedLetters($puzzle))->toBe($solutionWord->word);
});

it('leaves the solution word empty without failing the puzzle when nothing matches', function (): void {
    // BUKS shares no letter with the grid, so it can neither be placed nor
    // matched, and every other candidate ends up on the grid.
    $candidates = array_map(
        fn (array $candidate): WordCandidate => new WordCandidate($candidate[0], $candidate[1]),
        [['TIJGER', 'Grote kat'], ['IGLO', 'Huis van sneeuw'], ['REGEN', 'Uit de wolken'], ['BUKS', 'Een geweer']],
    );

    $puzzle = CrosswordPuzzle::factory()->create();

    expect($puzzle->relayout($candidates, [], minimumPlacements: 1))->toBeTrue();

    $puzzle->refresh();

    expect($puzzle->solution_word)->toBeNull()
        ->and($puzzle->entries)->not->toBeEmpty()
        ->and($puzzle->failed_at)->toBeNull();
});

it('only names the source text from level 21 up', function (): void {
    expect(CrosswordPuzzle::factory()->create(['level' => 20])->showsSource())->toBeFalse()
        ->and(CrosswordPuzzle::factory()->create(['level' => 21])->showsSource())->toBeTrue();
});
