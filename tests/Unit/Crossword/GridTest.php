<?php

declare(strict_types=1);

use App\Enums\Direction;
use App\Support\Crossword\Grid;
use App\Support\Crossword\Placement;
use App\Support\Crossword\WordCandidate;

function candidate(string $word, ?string $exerciseId = null): WordCandidate
{
    return new WordCandidate($word, "Aanwijzing voor {$word}", $exerciseId);
}

/** TIJGER across at (0,0) with IGLO hanging down from its I. */
function grid(): Grid
{
    $grid = new Grid;
    $grid->place(candidate('TIJGER'), 0, 0, Direction::Across);
    $grid->place(candidate('IGLO'), 0, 1, Direction::Down);

    return $grid;
}

it('places the first word without any crossing', function (): void {
    $grid = new Grid;

    expect($grid->place(candidate('TIJGER'), 0, 0, Direction::Across))->toBeTrue()
        ->and($grid->placements())->toHaveCount(1)
        ->and($grid->width())->toBe(6)
        ->and($grid->height())->toBe(1);
});

it('rejects a placement whose crossing letter does not match', function (): void {
    $grid = new Grid;
    $grid->place(candidate('TIJGER'), 0, 0, Direction::Across);

    expect($grid->crossingsAt(candidate('APPEL'), 0, 0, Direction::Down))->toBeNull();
});

it('rejects a word that would lie parallel against another word', function (): void {
    // AGENT would cross IGLO on its G, but run alongside TIJGER in the row below.
    expect(grid()->crossingsAt(candidate('AGENT'), 1, 0, Direction::Across))->toBeNull();
});

it('rejects a word whose head or tail square is filled', function (): void {
    $grid = new Grid;
    $grid->place(candidate('TIJGER'), 0, 0, Direction::Across);

    expect($grid->crossingsAt(candidate('RAT'), 0, 6, Direction::Across))->toBeNull()
        ->and($grid->crossingsAt(candidate('KAR'), 0, -3, Direction::Across))->toBeNull();
});

it('refuses a word that does not cross anything once the grid is not empty', function (): void {
    $grid = new Grid;
    $grid->place(candidate('TIJGER'), 0, 0, Direction::Across);

    expect($grid->place(candidate('WOLKEN'), 5, 5, Direction::Across))->toBeFalse()
        ->and($grid->placements())->toHaveCount(1);
});

it('counts crossings and double crossings over the whole grid', function (): void {
    $grid = grid();
    $grid->place(candidate('REGEN'), 0, 5, Direction::Down);

    expect($grid->crossings())->toBe(2)
        ->and($grid->doubleCrossings())->toBe(1); // TIJGER crosses both IGLO and REGEN
});

it('crops the grid to its bounding box', function (): void {
    $grid = new Grid;
    $grid->place(candidate('TIJGER'), 0, 0, Direction::Across);
    $grid->place(candidate('OLIFANT'), -2, 1, Direction::Down); // its I sits on TIJGER's I

    $entries = collect($grid->entries());

    expect($grid->height())->toBe(7)
        ->and($grid->width())->toBe(6)
        ->and($entries->min('row'))->toBe(0)
        ->and($entries->min('col'))->toBe(0)
        ->and($entries->firstWhere('word', 'TIJGER'))->toMatchArray(['row' => 2, 'col' => 0])
        ->and($entries->firstWhere('word', 'OLIFANT'))->toMatchArray(['row' => 0, 'col' => 1]);
});

it('gives an across and a down word starting on the same square the same number', function (): void {
    $grid = new Grid;
    $grid->place(candidate('TIJGER'), 0, 0, Direction::Across);
    $grid->place(candidate('TAK'), 0, 0, Direction::Down);
    $grid->place(candidate('REGEN'), 0, 5, Direction::Down);

    $entries = collect($grid->entries());

    expect($entries->firstWhere('word', 'TIJGER')['number'])->toBe(1)
        ->and($entries->firstWhere('word', 'TAK')['number'])->toBe(1)
        ->and($entries->firstWhere('word', 'REGEN')['number'])->toBe(2);
});

it('lists every valid crossing position for a candidate', function (): void {
    $placements = grid()->validPlacements(candidate('GEIT'));

    expect($placements)->not->toBeEmpty();

    foreach ($placements as $placement) {
        expect($placement->crossings)->toBeGreaterThanOrEqual(1)
            ->and($placement->word())->toBe('GEIT');
    }
});

it('offers the placements with the most crossings first', function (): void {
    // LayoutBuilder picks from the first few placements, so the order is a
    // contract: KOFFIE fits on row 3 across both IGLO and REGEN at once, and
    // that placement has to come before every single crossing.
    $grid = grid();
    $grid->place(candidate('REGEN'), 0, 5, Direction::Down);

    $placements = $grid->validPlacements(candidate('KOFFIE'));
    $crossings = array_map(fn (Placement $placement): int => $placement->crossings, $placements);

    expect($crossings[0])->toBe(2)
        ->and($placements[0])->toMatchObject(['row' => 3, 'column' => 0, 'direction' => Direction::Across])
        ->and($crossings)->toBe(collect($crossings)->sortDesc()->values()->all());
});
