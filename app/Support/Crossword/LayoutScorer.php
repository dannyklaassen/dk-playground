<?php

declare(strict_types=1);

namespace App\Support\Crossword;

class LayoutScorer
{
    private const int DOUBLE_CROSSING_WEIGHT = 15;

    private const int CROSSING_WEIGHT = 10;

    private const int PLACED_WORD_WEIGHT = 5;

    private const int SIZE_PENALTY = 2;

    private const int IMBALANCE_PENALTY = 1;

    /** Every source text should contribute at least one word; a soft rule, not a hard one. */
    private const int MISSING_EXERCISE_PENALTY = 25;

    /**
     * @param  list<string>  $exerciseIds
     */
    public function score(Grid $grid, array $exerciseIds = []): int
    {
        $covered = array_filter(array_map(
            fn (Placement $placement): ?string => $placement->candidate->exerciseId,
            $grid->placements(),
        ));

        $missing = count(array_diff($exerciseIds, $covered));

        return self::DOUBLE_CROSSING_WEIGHT * $grid->doubleCrossings()
            + self::CROSSING_WEIGHT * $grid->crossings()
            + self::PLACED_WORD_WEIGHT * count($grid->placements())
            - self::SIZE_PENALTY * ($grid->width() + $grid->height())
            - self::IMBALANCE_PENALTY * abs($grid->width() - $grid->height())
            - self::MISSING_EXERCISE_PENALTY * $missing;
    }
}
