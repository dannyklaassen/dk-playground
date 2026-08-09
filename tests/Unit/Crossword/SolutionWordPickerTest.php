<?php

declare(strict_types=1);

use App\Enums\Direction;
use App\Support\Crossword\SolutionCell;
use App\Support\Crossword\SolutionWordPicker;
use App\Support\Crossword\WordCandidate;

/**
 * @return array<string, mixed>
 */
function solutionEntry(string $word, int $row, int $col, Direction $direction = Direction::Across): array
{
    return [
        'word' => $word,
        'clue' => "Aanwijzing voor {$word}",
        'exercise_id' => null,
        'row' => $row,
        'col' => $col,
        'direction' => $direction->value,
        'number' => 1,
    ];
}

function solutionCandidate(string $word): WordCandidate
{
    return new WordCandidate($word, "Aanwijzing voor {$word}");
}

/** Eight across words, each on its own row, so no two entries share a square. */
function looseEntries(): array
{
    return [
        solutionEntry('TIJGER', 0, 0),
        solutionEntry('WOLKEN', 2, 0),
        solutionEntry('PLANTEN', 4, 0),
        solutionEntry('ZEBRA', 6, 0),
        solutionEntry('KIKKER', 8, 0),
        solutionEntry('SNEEUW', 10, 0),
        solutionEntry('OLIFANT', 12, 0),
        solutionEntry('RIVIER', 14, 0),
    ];
}

/**
 * @param  list<array<string, mixed>>  $entries
 */
function letterAt(array $entries, SolutionCell $cell): string
{
    foreach ($entries as $entry) {
        foreach (mb_str_split($entry['word']) as $offset => $letter) {
            $row = $entry['row'] + ($entry['direction'] === Direction::Down->value ? $offset : 0);
            $column = $entry['col'] + ($entry['direction'] === Direction::Across->value ? $offset : 0);

            if ($row === $cell->row && $column === $cell->column) {
                return $letter;
            }
        }
    }

    return '';
}

it('assigns one square per letter, in the order of the word', function (): void {
    $entries = looseEntries();

    $solution = (new SolutionWordPicker)->pick($entries, [solutionCandidate('BERGEN')]);

    expect($solution)->not->toBeNull()
        ->and($solution->word)->toBe('BERGEN')
        ->and($solution->clue)->toBe('Aanwijzing voor BERGEN')
        ->and($solution->cells)->toHaveCount(6);

    $letters = array_map(fn (SolutionCell $cell): string => letterAt($entries, $cell), $solution->cells);
    $keys = array_map(fn (SolutionCell $cell): string => "{$cell->row},{$cell->column}", $solution->cells);

    expect(implode('', $letters))->toBe('BERGEN')
        ->and(array_unique($keys))->toHaveCount(6);
});

it('spreads the squares over different entries', function (): void {
    $entries = looseEntries();

    $solution = (new SolutionWordPicker)->pick($entries, [solutionCandidate('BERGEN')]);

    // Every entry sits on its own row, so a distinct row is a distinct entry.
    expect(array_unique(array_map(fn (SolutionCell $cell): int => $cell->row, $solution->cells)))->toHaveCount(6);
});

it('backtracks so a word is not lost to an early letter taking the last square', function (): void {
    // The only G is in TIJGER and the only B is in ZEBRA, while the E and the R
    // have to dodge both.
    $entries = [
        solutionEntry('TIJGER', 0, 0),
        solutionEntry('ZEBRA', 2, 0),
        solutionEntry('WOLKEN', 4, 0),
        solutionEntry('RIVIER', 6, 0),
        solutionEntry('SNEEUW', 8, 0),
        solutionEntry('PLANTEN', 10, 0),
    ];

    $solution = (new SolutionWordPicker)->pick($entries, [solutionCandidate('BERGEN')]);

    expect($solution?->word)->toBe('BERGEN')
        ->and(array_unique(array_map(fn (SolutionCell $cell): int => $cell->row, $solution->cells)))->toHaveCount(6);
});

it('prefers a shorter spread word over a longer word that only fits clustered', function (): void {
    $entries = [
        solutionEntry('AAAAAA', 0, 0),
        solutionEntry('BXX', 2, 0),
        solutionEntry('CXX', 4, 0),
        solutionEntry('DXX', 6, 0),
    ];

    $solution = (new SolutionWordPicker)->pick($entries, [
        solutionCandidate('AAAA'),
        solutionCandidate('BCD'),
    ]);

    expect($solution?->word)->toBe('BCD');
});

it('drops the cap only when no candidate fits with one square per entry', function (): void {
    $entries = [
        solutionEntry('AAAAAA', 0, 0),
        solutionEntry('BXX', 2, 0),
    ];

    $solution = (new SolutionWordPicker)->pick($entries, [solutionCandidate('AAAA')]);

    expect($solution?->word)->toBe('AAAA')
        ->and($solution->cells)->toHaveCount(4);
});

it('puts crossings and starting squares last, and a square that is both behind everything', function (): void {
    $entries = [
        solutionEntry('AAAAZ', 1, 0),
        // Crosses the A word at (1,2) without starting there.
        solutionEntry('XA', 0, 2, Direction::Down),
        // Starts on the crossing at (1,3): both a starting square and a crossing.
        solutionEntry('AY', 1, 3, Direction::Down),
    ];

    $solution = (new SolutionWordPicker)->pick($entries, [solutionCandidate('AAAA')]);

    expect(array_map(fn (SolutionCell $cell): array => $cell->toArray(), $solution->cells))->toBe([
        ['row' => 1, 'col' => 1], // plain
        ['row' => 1, 'col' => 0], // starting square
        ['row' => 1, 'col' => 2], // crossing
        ['row' => 1, 'col' => 3], // both
    ]);
});

it('never picks a word of more than eight letters', function (): void {
    $entries = [
        solutionEntry('AAAAAAAAAB', 0, 0),
        solutionEntry('BXX', 2, 0),
    ];

    $solution = (new SolutionWordPicker)->pick($entries, [
        solutionCandidate('AAAAAAAAA'),
        solutionCandidate('AAAAAAAA'),
    ]);

    expect($solution?->word)->toBe('AAAAAAAA');
});

it('leaves the placed words out of the stock', function (): void {
    $entries = looseEntries();

    $solution = (new SolutionWordPicker)->pick($entries, [
        solutionCandidate('OLIFANT'),
        solutionCandidate('BERGEN'),
    ]);

    expect($solution?->word)->toBe('BERGEN');
});

it('makes the same choice every time', function (): void {
    $entries = looseEntries();
    $candidates = [solutionCandidate('BERGEN'), solutionCandidate('KOREN'), solutionCandidate('SLOOT')];

    $first = (new SolutionWordPicker)->pick($entries, $candidates);
    $second = (new SolutionWordPicker)->pick($entries, $candidates);

    expect($first?->toArray())->toBe($second?->toArray());
});

it('gives no solution word when no candidate can be matched', function (): void {
    $solution = (new SolutionWordPicker)->pick(looseEntries(), [solutionCandidate('QUIZZEN')]);

    expect($solution)->toBeNull();
});
