<?php

declare(strict_types=1);

use App\Enums\Direction;
use App\Support\Crossword\Grid;
use App\Support\Crossword\LayoutScorer;
use App\Support\Crossword\WordCandidate;

/**
 * TIJGER across, with IGLO and REGEN hanging down from it: 6 x 5, two
 * crossings, and TIJGER as the single double-crossing word.
 */
function compactGrid(?string $exerciseId = null): Grid
{
    $grid = new Grid;
    $grid->place(new WordCandidate('TIJGER', 'Grote kat', $exerciseId), 0, 0, Direction::Across);
    $grid->place(new WordCandidate('IGLO', 'Sneeuwhuis', $exerciseId), 0, 1, Direction::Down);
    $grid->place(new WordCandidate('REGEN', 'Uit de wolken', $exerciseId), 0, 5, Direction::Down);

    return $grid;
}

it('scores a grid with the weights from the spec', function (): void {
    // 15 x 1 double crossing + 10 x 2 crossings + 5 x 3 words - 2 x (6 + 5) - 1 x 1
    expect((new LayoutScorer)->score(compactGrid()))->toBe(27);
});

it('prefers the more compact of two grids with the same words and crossings', function (): void {
    $sprawling = new Grid;
    $sprawling->place(new WordCandidate('TIJGER', 'Grote kat'), 0, 0, Direction::Across);
    $sprawling->place(new WordCandidate('IGLO', 'Sneeuwhuis'), -1, 3, Direction::Down); // hangs above the row
    $sprawling->place(new WordCandidate('REGEN', 'Uit de wolken'), 0, 5, Direction::Down);

    $scorer = new LayoutScorer;

    expect($sprawling->crossings())->toBe(compactGrid()->crossings())
        ->and($sprawling->doubleCrossings())->toBe(compactGrid()->doubleCrossings())
        ->and($scorer->score($sprawling))->toBeLessThan($scorer->score(compactGrid()));
});

it('prefers a grid whose words cross two others over one that crosses only one', function (): void {
    $chain = new Grid;
    $chain->place(new WordCandidate('TIJGER', 'Grote kat'), 0, 0, Direction::Across);
    $chain->place(new WordCandidate('IGLO', 'Sneeuwhuis'), 0, 1, Direction::Down);

    $woven = clone compactGrid();

    expect($chain->doubleCrossings())->toBe(0)
        ->and($woven->doubleCrossings())->toBe(1)
        ->and((new LayoutScorer)->score($woven))->toBeGreaterThan((new LayoutScorer)->score($chain));
});

it('subtracts 25 points for every source text without a word in the grid', function (): void {
    $scorer = new LayoutScorer;
    $grid = compactGrid('exercise-a');

    expect($scorer->score($grid, ['exercise-a']))
        ->toBe($scorer->score($grid, ['exercise-a', 'exercise-b']) + 25);
});
