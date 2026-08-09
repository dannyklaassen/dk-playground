<?php

declare(strict_types=1);

use App\Support\Crossword\Grid;
use App\Support\Crossword\LayoutBuilder;
use App\Support\Crossword\WordCandidate;

/**
 * @return list<WordCandidate>
 */
function candidates(): array
{
    $words = [
        'TIJGER', 'IGLO', 'REGEN', 'OLIFANT', 'WOLKEN', 'PLANTEN', 'ZEBRA', 'KIKKER', 'BOOM', 'NEST',
        'VLINDER', 'ZANDBAK', 'KORAAL', 'STRAND', 'WOESTIJN', 'GLETSJER', 'KOMEET', 'PLANEET', 'RAKET',
        'SNEEUW', 'STORM', 'BLIKSEM', 'DONDER', 'RIVIER', 'BERGEN', 'BOSSEN', 'VOGELS', 'SLANGEN',
    ];

    return array_map(
        fn (string $word, int $index): WordCandidate => new WordCandidate(
            $word,
            "Aanwijzing voor {$word}",
            'exercise-'.($index % 2 === 0 ? 'a' : 'b'),
        ),
        $words,
        array_keys($words),
    );
}

it('lays a connected grid in which every word after the first crosses another', function (): void {
    $grid = (new LayoutBuilder)->build(candidates(), 14, ['exercise-a', 'exercise-b'], 42);

    expect($grid->placements())->toHaveCount(14);

    foreach (array_slice($grid->placements(), 1) as $placement) {
        expect($placement->crossings)->toBeGreaterThanOrEqual(1);
    }
});

it('never places more words than the target count', function (): void {
    $grid = (new LayoutBuilder)->build(candidates(), 4, [], 42);

    expect($grid->placements())->toHaveCount(4);
});

it('produces the same grid twice for the same seed', function (): void {
    $builder = new LayoutBuilder;

    expect($builder->build(candidates(), 14, [], 7)->entries())
        ->toBe($builder->build(candidates(), 14, [], 7)->entries());
});

it('produces different grids for different seeds', function (): void {
    $builder = new LayoutBuilder;

    $layouts = array_map(
        fn (int $seed): array => $builder->build(candidates(), 14, [], $seed)->entries(),
        range(1, 5),
    );

    expect(array_unique(array_map(serialize(...), $layouts)))->not->toHaveCount(1);
});

it('skips a candidate that fits nowhere', function (): void {
    $words = [
        new WordCandidate('TIJGER', 'Grote kat'),
        new WordCandidate('XYZW', 'Past nergens'), // shares no letter with TIJGER
    ];

    $grid = (new LayoutBuilder(attempts: 5))->build($words, 2, [], 1);

    expect($grid->placements())->toHaveCount(1);
});

it('keeps the best scoring grid instead of failing on an unreachable quota', function (): void {
    // exercise-c contributes no candidates at all, so its quota can never be met.
    $grid = (new LayoutBuilder)->build(candidates(), 14, ['exercise-a', 'exercise-b', 'exercise-c'], 3);

    expect($grid)->toBeInstanceOf(Grid::class)
        ->and($grid->placements())->not->toBeEmpty();
});
