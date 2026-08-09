<?php

declare(strict_types=1);

namespace App\Support\Crossword;

use App\Enums\Direction;
use Closure;

/**
 * Lays a criss-cross grid by trying many shuffled word orders and keeping the
 * best scoring one. Deterministic: the same candidates and seed always give the
 * same grid.
 */
class LayoutBuilder
{
    /**
     * How many of the best positions for a word are eligible. Always taking the
     * single best one makes the search converge on one layout for every seed,
     * which would leave "opnieuw leggen" with nothing to reroll.
     */
    private const int POSITION_CHOICES = 3;

    public function __construct(
        private readonly int $attempts = 300,
        private readonly LayoutScorer $scorer = new LayoutScorer,
    ) {}

    /**
     * @param  list<WordCandidate>  $candidates
     * @param  list<string>  $exerciseIds
     */
    public function build(array $candidates, int $targetCount, array $exerciseIds, int $seed): Grid
    {
        $best = new Grid;
        $bestScore = PHP_INT_MIN;

        foreach (range(1, $this->attempts) as $attempt) {
            $random = $this->generator($seed * 1000003 + $attempt);
            $grid = $this->lay($this->shuffle($candidates, $random), $targetCount, $random);
            $score = $this->scorer->score($grid, $exerciseIds);

            if ($score > $bestScore) {
                $best = $grid;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * @param  list<WordCandidate>  $candidates
     */
    private function lay(array $candidates, int $targetCount, Closure $random): Grid
    {
        $grid = new Grid;

        foreach ($candidates as $candidate) {
            if (count($grid->placements()) >= $targetCount) {
                break;
            }

            if ($grid->isEmpty()) {
                $grid->place($candidate, 0, 0, Direction::Across);

                continue;
            }

            $placements = $grid->validPlacements($candidate);

            if ($placements === []) {
                continue;
            }

            $placement = $placements[$random(min(self::POSITION_CHOICES, count($placements)))];

            $grid->place($candidate, $placement->row, $placement->column, $placement->direction);
        }

        return $grid;
    }

    /**
     * @param  list<WordCandidate>  $candidates
     * @return list<WordCandidate>
     */
    private function shuffle(array $candidates, Closure $random): array
    {
        for ($index = count($candidates) - 1; $index > 0; $index--) {
            $target = $random($index + 1);

            [$candidates[$index], $candidates[$target]] = [$candidates[$target], $candidates[$index]];
        }

        return $candidates;
    }

    /**
     * A tiny seeded generator, so the global random state stays untouched and
     * the whole layout stays reproducible from the stored seed.
     *
     * @return Closure(int): int
     */
    private function generator(int $seed): Closure
    {
        $state = abs($seed) % 2147483647 + 1;

        // The low bits of a power-of-two LCG barely move, so only the high bits
        // are usable for a small bound.
        return function (int $bound) use (&$state): int {
            $state = ($state * 1103515245 + 12345) % 2147483648;

            return intdiv($state, 65536) % $bound;
        };
    }
}
