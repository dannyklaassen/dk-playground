<?php

declare(strict_types=1);

use App\Enums\Direction;
use App\Models\CrosswordPuzzle;

it('rebuilds the grid matrix from the stored entries', function (): void {
    $cells = CrosswordPuzzle::factory()->generated()->create()->cells();

    expect($cells)->toHaveCount(5)
        ->and($cells[0])->toHaveCount(6)
        ->and($cells[0][0])->toBe(['letter' => 'T', 'number' => 1])
        ->and($cells[0][1])->toBe(['letter' => 'I', 'number' => 2])
        ->and($cells[0][5])->toBe(['letter' => 'R', 'number' => 3])
        ->and($cells[0][2])->toBe(['letter' => 'J', 'number' => null])
        ->and($cells[1][1])->toBe(['letter' => 'G', 'number' => null])
        ->and($cells[4][5])->toBe(['letter' => 'N', 'number' => null])
        ->and($cells[1][0])->toBeNull()
        ->and($cells[4][0])->toBeNull();
});

it('caps the printed cell size at 12mm and scales it down for a wide grid', function (): void {
    expect(CrosswordPuzzle::factory()->generated()->create()->cellSizeMm())->toBe(12.0)
        ->and(CrosswordPuzzle::factory()->generated()->create(['grid_cols' => 20])->cellSizeMm())->toBe(8.5);
});

it('splits the entries by direction, ordered by number', function (): void {
    $puzzle = CrosswordPuzzle::factory()->generated()->create();

    expect(array_column($puzzle->entriesFor(Direction::Across), 'word'))->toBe(['TIJGER'])
        ->and(array_column($puzzle->entriesFor(Direction::Down), 'word'))->toBe(['IGLO', 'REGEN']);
});

it('only names the source text from level 21 up', function (): void {
    expect(CrosswordPuzzle::factory()->create(['level' => 20])->showsSource())->toBeFalse()
        ->and(CrosswordPuzzle::factory()->create(['level' => 21])->showsSource())->toBeTrue();
});
