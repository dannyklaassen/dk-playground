<?php

declare(strict_types=1);

namespace App\Support\Crossword;

use InvalidArgumentException;

/**
 * The solution word: the word itself, the clue of the candidate it came from,
 * and one grid square per letter. The index of a cell plus one is the number
 * printed in that square.
 */
readonly class SolutionWord
{
    /** Eight squares of 12mm fit within the printable width of an A4. */
    public const int MAX_LENGTH = 8;

    /**
     * @param  list<SolutionCell>  $cells
     */
    public function __construct(
        public string $word,
        public string $clue,
        public array $cells,
    ) {}

    /**
     * The label of the n-th square, counted from zero: a, b, c and so on.
     * Letters, not digits, so a marked square can never be read as the numbered
     * start of an entry.
     */
    public static function label(int $index): string
    {
        // Past the cap chr() runs into punctuation, which would print as a
        // marking nobody can read back. Fail here instead of on paper.
        if ($index < 0 || $index >= self::MAX_LENGTH) {
            throw new InvalidArgumentException("Solution word square {$index} lies outside the ".self::MAX_LENGTH.' squares a sheet can hold.');
        }

        return chr(ord('a') + $index);
    }

    /**
     * Reads the stored column. Every key is optional: a half-written value
     * degrades to an empty solution word instead of a 500 halfway through a
     * printed sheet.
     *
     * @param  array<string, mixed>  $solutionWord
     */
    public static function fromArray(array $solutionWord): self
    {
        return new self(
            (string) ($solutionWord['word'] ?? ''),
            (string) ($solutionWord['clue'] ?? ''),
            array_map(
                fn (array $cell): SolutionCell => new SolutionCell((int) ($cell['row'] ?? 0), (int) ($cell['col'] ?? 0)),
                array_values($solutionWord['cells'] ?? []),
            ),
        );
    }

    /**
     * @return array{word: string, clue: string, cells: list<array{row: int, col: int}>}
     */
    public function toArray(): array
    {
        return [
            'word' => $this->word,
            'clue' => $this->clue,
            'cells' => array_map(fn (SolutionCell $cell): array => $cell->toArray(), $this->cells),
        ];
    }
}
