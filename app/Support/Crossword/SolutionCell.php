<?php

declare(strict_types=1);

namespace App\Support\Crossword;

/** One marked square of the solution word, in the cropped coordinates of the entries. */
readonly class SolutionCell
{
    public function __construct(
        public int $row,
        public int $column,
    ) {}

    /**
     * @return array{row: int, col: int}
     */
    public function toArray(): array
    {
        return [
            'row' => $this->row,
            'col' => $this->column,
        ];
    }
}
