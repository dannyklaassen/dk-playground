<?php

declare(strict_types=1);

namespace App\Support\Crossword;

use App\Enums\Direction;

readonly class Placement
{
    public function __construct(
        public WordCandidate $candidate,
        public int $row,
        public int $column,
        public Direction $direction,
        public int $crossings,
    ) {}

    public function word(): string
    {
        return $this->candidate->word;
    }
}
